<?php

namespace Tests\Feature;

use App\Ai\Agents\LexTrackAssistant;
use App\Models\User;
use App\Services\ClientMessageAvailabilityService;
use App\Services\ClientDocumentLookupService;
use App\Services\ChatbotIntentRouter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Mockery;
use Tests\TestCase;

class ChatbotRoutingTest extends TestCase
{
    public function test_assistant_knowledge_context_redacts_example_lao_numbers(): void
    {
        $instructions = (new LexTrackAssistant)->instructions();

        $this->assertDoesNotMatchRegularExpression(
            '~\bLAO[\s./#:-]*\d[\p{L}\p{N}./#:-]*~iu',
            $instructions,
        );
    }

    public function test_assistant_instructions_require_direct_yes_no_and_concise_relevant_answers(): void
    {
        $instructions = (new LexTrackAssistant)->instructions();

        $this->assertStringContainsString('start with a direct Yes/No or Oo/Hindi answer', $instructions);
        $this->assertStringContainsString('Do not repeat a procedure', $instructions);
        $this->assertStringContainsString('append office contact information', $instructions);
        $this->assertStringContainsString('Do not claim personal-email notification support', $instructions);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('documents', function (Blueprint $table): void {
            $table->id('document_id');
            $table->unsignedBigInteger('user_id');
            $table->string('document_type')->nullable();
            $table->string('status');
            $table->string('action_type')->nullable();
            $table->string('sent_to')->nullable();
            $table->date('sent_date')->nullable();
            $table->text('particulars')->nullable();
            $table->string('lao_number')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default(User::DEFAULT_STATUS);
        });

        Schema::connection('sqlite')->create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
        });

        Schema::connection('sqlite')->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unique(['role_id', 'model_id', 'model_type']);
        });

        Schema::connection('sqlite')->create('conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('message_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');
        });
    }

    public function test_authenticated_client_receives_latest_owned_document_status_only(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now()->subDay());
        $this->insertDocument(17, 'in_progress', now(), [
            'action_type' => 'PRIVATE_ACTION_FIELD',
            'sent_to' => 'PRIVATE_DESTINATION_FIELD',
            'particulars' => 'PRIVATE_PARTICULARS_FIELD',
            'rejection_reason' => 'PRIVATE_REJECTION_FIELD',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest submitted document?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Your latest submitted document is currently In Progress.',
            ])
            ->assertDontSee('PRIVATE_ACTION_FIELD')
            ->assertDontSee('PRIVATE_DESTINATION_FIELD')
            ->assertDontSee('PRIVATE_PARTICULARS_FIELD')
            ->assertDontSee('PRIVATE_REJECTION_FIELD');
    }

    public function test_exact_latest_status_question_uses_submission_date_without_offering_choices(): void
    {
        $this->actingAsClient(17);
        // The higher database ID is older, so the submission timestamp must win.
        $this->insertDocument(17, 'in_progress', now(), ['document_id' => 100]);
        $this->insertDocument(17, 'pending', now()->subDay(), ['document_id' => 999]);
        $this->insertDocument(29, 'rejected', now()->addDay(), ['document_id' => 1000]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest document?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Your latest submitted document is currently In Progress.',
            ])
            ->assertDontSee('Which one do you mean?')
            ->assertDontSee('Rejected');
    }

    public function test_pending_workflow_follow_up_uses_only_a_sanitized_knowledge_prompt(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now(), ['lao_number' => 'LAO-26-701']);

        $prompts = [];
        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')->times(3)->andReturn(true);
        $assistant->shouldReceive('prompt')
            ->times(3)
            ->withArgs(function (mixed ...$arguments) use (&$prompts): bool {
                $arguments = array_values($arguments);
                $prompts[] = (string) ($arguments[0] ?? '');

                return in_array(Lab::OpenAI, $arguments, true)
                    && in_array('gpt-5-mini', $arguments, true);
            })
            ->andReturn(
                new AgentResponse(
                    'workflow-one',
                    'Nagiging In Progress ito kapag nasuri at tinanggap ng Legal Affairs Office. Hindi garantisado ang pagtanggap.',
                    new Usage,
                    new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
                ),
                new AgentResponse(
                    'workflow-two',
                    'Nagiging In Progress lamang ito matapos suriin at tanggapin ng Legal Affairs Office.',
                    new Usage,
                    new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
                ),
                new AgentResponse(
                    'workflow-three',
                    'Kailangang suriin at tanggapin muna ito ng Legal Affairs Office; walang garantisadong acceptance.',
                    new Usage,
                    new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
                ),
            );
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest submitted document?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Your latest submitted document is currently Pending. It is awaiting initial review and validation by the Legal Affairs Office.',
            ]);

        $acceptanceReply = $this->postJson('/chatbot/message', ['message' => 'Pano ba siya magiging In Progress?']);
        $acceptanceReply->assertOk()->assertDontSee('LAO-26-701');
        $this->assertStringContainsString('nasuri at tinanggap', (string) $acceptanceReply->json('reply'));

        $repeatedWorkflowReply = $this->postJson('/chatbot/message', [
            'message' => 'Paano siya magiging In Progress?',
        ]);
        $repeatedWorkflowReply->assertOk()->assertDontSee('LAO-26-701');
        $this->assertStringContainsString('suriin at tanggapin', (string) $repeatedWorkflowReply->json('reply'));

        $acceptanceProcessReply = $this->postJson('/chatbot/message', ['message' => 'Pano ba sya maaccept?']);
        $acceptanceProcessReply->assertOk()->assertDontSee('LAO-26-701');
        $this->assertStringContainsString('suriin at tanggapin', (string) $acceptanceProcessReply->json('reply'));

        $this->assertSame([
            'Ipaliwanag nang maikli kung paano nagiging In Progress ang isang Pending document.',
            'Ipaliwanag nang maikli kung paano nagiging In Progress ang isang Pending document.',
            'Ipaliwanag nang maikli kung paano nagiging In Progress ang isang Pending document.',
        ], $prompts);
        $this->assertStringNotContainsString('Pano ba siya', implode('\n', $prompts));
        $this->assertStringNotContainsString('LAO-26-701', implode('\n', $prompts));
        $this->assertNotSame(
            $acceptanceReply->json('reply'),
            $repeatedWorkflowReply->json('reply'),
            'Repeated workflow questions must not be answered with a cached status-only reply.',
        );

        $this->postJson('/chatbot/message', ['message' => 'Can it be accepted?'])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Yes. It may be accepted after the Legal Affairs Office completes its initial review and confirms that the submission meets the requirements.',
            ]);

        $this->postJson('/chatbot/message', ['message' => 'Accepted na ba siya?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'No. It is still Pending and awaiting its initial review.']);
    }

    public function test_rejected_resubmission_follow_up_is_distinguished_from_an_open_revision(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'rejected', now(), [
            'lao_number' => 'LAO-26-702',
            'rejection_reason' => 'PRIVATE_REJECTION_REASON',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'What is the status of LAO-26-702?'])
            ->assertOk()
            ->assertSee('Rejected');

        $reply = $this->postJson('/chatbot/message', ['message' => 'Can I resubmit it?']);
        $reply->assertOk()
            ->assertSee('Rejected')
            ->assertSee('Submit Document')
            ->assertSee('authorized open request')
            ->assertDontSee('PRIVATE_REJECTION_REASON')
            ->assertDontSee('LAO-26-702');
    }

    public function test_document_counts_are_scoped_to_the_authenticated_client_and_status(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now()->subDays(2));
        $this->insertDocument(17, 'pending', now()->subDay());
        $this->insertDocument(17, 'outgoing', now());
        $this->insertDocument(29, 'pending', now());

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'Ilang pending documents ko?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'Mayroon kang 2 documents na may status na Pending.']);

        $this->postJson('/chatbot/message', ['message' => 'Do I have pending documents?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'Yes, you have 2 documents with status Pending.']);

        $this->postJson('/chatbot/message', ['message' => 'How many documents do I have?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'You have 3 authorized documents in LexTrack.']);
    }

    public function test_message_metadata_counts_only_authorized_conversations_without_reading_bodies(): void
    {
        $this->actingAsClient(17);
        $this->insertOfficeSender(99, 'Admin');
        $this->insertOfficeSender(98, 'Client');
        $this->insertDocument(17, 'in_progress', now(), ['lao_number' => 'LAO-26-801']);
        $ownedDocumentId = (int) DB::table('documents')->where('user_id', 17)->value('document_id');
        $ownedConversationId = $this->insertConversation($ownedDocumentId, 17);
        $this->insertMessage($ownedConversationId, 99, 'PRIVATE_MESSAGE_BODY_UNREAD');
        $this->insertMessage($ownedConversationId, 17, 'PRIVATE_MESSAGE_BODY_SENT_BY_CLIENT');
        $this->insertMessage($ownedConversationId, 98, 'PRIVATE_MESSAGE_BODY_UNAUTHORIZED_SENDER');
        $readMessageId = $this->insertMessage($ownedConversationId, 99, 'PRIVATE_MESSAGE_BODY_READ');
        DB::table('message_reads')->insert([
            'message_id' => $readMessageId,
            'user_id' => 17,
            'read_at' => now(),
        ]);

        $this->insertDocument(17, 'pending', now(), ['lao_number' => null]);
        $pendingDocumentId = (int) DB::table('documents')->where('status', 'pending')->value('document_id');
        $pendingConversationId = $this->insertConversation($pendingDocumentId, 17);
        $this->insertMessage($pendingConversationId, 99, 'PRIVATE_PENDING_CONVERSATION');

        $this->insertDocument(29, 'in_progress', now(), ['lao_number' => 'LAO-26-899']);
        $otherDocumentId = (int) DB::table('documents')->where('user_id', 29)->value('document_id');
        $otherConversationId = $this->insertConversation($otherDocumentId, 17);
        $this->insertMessage($otherConversationId, 99, 'PRIVATE_OTHER_CLIENT_CONVERSATION');

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson('/chatbot/message', ['message' => 'Do I have unread messages?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'Yes, you have 1 unread message from the Legal Affairs Office. Open Messages to read it.'])
            ->assertDontSee('PRIVATE_MESSAGE_BODY_UNREAD')
            ->assertDontSee('PRIVATE_PENDING_CONVERSATION')
            ->assertDontSee('PRIVATE_OTHER_CLIENT_CONVERSATION');

        $this->postJson('/chatbot/message', ['message' => 'How many messages do I have?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'You have 2 messages from the Legal Affairs Office. Open Messages to view them.'])
            ->assertDontSee('PRIVATE_MESSAGE_BODY_SENT_BY_CLIENT');

        $this->postJson('/chatbot/message', ['message' => 'Do I have messages?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'Yes, you have a message from the Legal Affairs Office. Open Messages in the Client Portal to read it.'])
            ->assertDontSee('PRIVATE_MESSAGE_BODY_UNAUTHORIZED_SENDER');

        $messageQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(static fn (string $query): bool => str_contains(strtolower($query), 'messages'));

        $this->assertNotEmpty($messageQueries);
        foreach ($messageQueries as $query) {
            $this->assertStringNotContainsString('body', strtolower($query));
        }
    }

    public function test_message_availability_requires_an_owned_participating_conversation_and_authorized_active_sender(): void
    {
        $this->actingAsClient(17);
        $this->insertOfficeSender(99, 'Admin');
        $this->insertOfficeSender(98, 'Admin', 'Inactive');
        $this->insertOfficeSender(97, 'Client');

        $this->insertDocument(17, 'in_progress', now(), ['lao_number' => 'LAO-26-811']);
        $ownedDocumentId = (int) DB::table('documents')->where('user_id', 17)->value('document_id');
        $ownedConversationId = $this->insertConversation($ownedDocumentId, 17);
        $this->insertMessage($ownedConversationId, 97, 'CLIENT_SENDER_NOT_OFFICE');
        $this->insertMessage($ownedConversationId, 98, 'INACTIVE_SENDER_NOT_CURRENT_STAFF');

        $this->insertDocument(29, 'in_progress', now(), ['lao_number' => 'LAO-26-812']);
        $otherDocumentId = (int) DB::table('documents')->where('user_id', 29)->value('document_id');
        $otherConversationId = $this->insertConversation($otherDocumentId, 17);
        $this->insertMessage($otherConversationId, 99, 'OTHER_CLIENT_DOCUMENT_MESSAGE');

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'Do I have messages?'])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'I found no message from the Legal Affairs Office in conversations available to your account. Check Messages in the Client Portal.',
            ]);

        $this->insertMessage($ownedConversationId, 99, 'AUTHORIZED_OFFICE_MESSAGE');

        $this->postJson('/chatbot/message', ['message' => 'May message ba sa akin ang legal?'])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Oo, may mensahe sa iyo mula sa Legal Affairs Office. Buksan ang Messages sa Client Portal para mabasa ito.',
            ])
            ->assertDontSee('AUTHORIZED_OFFICE_MESSAGE')
            ->assertDontSee('OTHER_CLIENT_DOCUMENT_MESSAGE');
    }

    public function test_private_message_content_requests_are_answered_locally_without_reading_messages(): void
    {
        $this->actingAsClient(17);

        $messages = Mockery::mock(ClientMessageAvailabilityService::class);
        $messages->shouldNotReceive('hasMessagesFromOffice');
        $messages->shouldNotReceive('countMessagesFromOffice');
        $messages->shouldNotReceive('countUnreadMessagesFromOffice');
        $this->app->instance(ClientMessageAvailabilityService::class, $messages);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $expectedEnglish = 'For privacy, I can’t read or summarize private messages here. Open Messages in your Client Portal to view the conversation with the Legal Affairs Office.';
        $expectedFilipino = 'Para mapanatili ang privacy, hindi ko mababasa o maibubuod ang private messages. Buksan ang Messages sa Client Portal para makita ang usapan sa Legal Affairs Office.';

        foreach ([
            'What’s the message about?' => $expectedEnglish,
            'Ano ang sinabi ng legal?' => $expectedFilipino,
            'Ano ang sinabi ng legal about my message?' => $expectedFilipino,
            'Please read and summarize my messages.' => $expectedEnglish,
        ] as $question => $expectedReply) {
            $this->postJson('/chatbot/message', ['message' => $question])
                ->assertOk()
                ->assertExactJson(['reply' => $expectedReply])
                ->assertDontSee('PRIVATE_MESSAGE_BODY');
        }
    }

    public function test_follow_up_about_private_message_content_stays_local(): void
    {
        $this->actingAsClient(17);

        $messages = Mockery::mock(ClientMessageAvailabilityService::class);
        $messages->shouldReceive('hasMessagesFromOffice')->once()->andReturn(true);
        $messages->shouldNotReceive('countMessagesFromOffice');
        $messages->shouldNotReceive('countUnreadMessagesFromOffice');
        $this->app->instance(ClientMessageAvailabilityService::class, $messages);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'Do I have messages?'])->assertOk();

        $this->postJson('/chatbot/message', ['message' => 'What was it about?'])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'For privacy, I can’t read or summarize private messages here. Open Messages in your Client Portal to view the conversation with the Legal Affairs Office.',
            ]);
    }

    public function test_acknowledgments_and_personal_email_delivery_questions_stay_local(): void
    {
        $this->actingAsClient(17);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        foreach (['Thanks', 'Salamat po', 'Sige po'] as $message) {
            $this->postJson('/chatbot/message', ['message' => $message])->assertOk();
        }

        $this->postJson('/chatbot/message', ['message' => 'Did I receive an email for my document?'])
            ->assertOk()
            ->assertSee('can’t verify delivery of a specific email')
            ->assertSee('@bicol-u.edu.ph')
            ->assertDontSee('PRIVATE_EMAIL');
    }

    public function test_client_cannot_retrieve_another_users_document_status(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(29, 'completed', now(), [
            'particulars' => 'OTHER_CLIENT_PRIVATE_PARTICULARS',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest submitted document?',
            'document_id' => 1,
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'You have no submitted documents yet.',
            ])
            ->assertDontSee('OTHER_CLIENT_PRIVATE_PARTICULARS')
            ->assertDontSee('Completed');
    }

    public function test_empty_document_lookup_returns_the_database_fallback(): void
    {
        $this->actingAsClient(17);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest submitted document?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'You have no submitted documents yet.',
            ]);
    }

    public function test_private_status_lookup_never_checks_or_invokes_openai(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'outgoing', now());

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of my latest submitted document?',
            'history' => [
                ['role' => 'assistant', 'content' => 'PRIVATE_CONVERSATION_HISTORY'],
            ],
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Your latest submitted document is currently Outgoing.',
            ])
            ->assertDontSee('PRIVATE_CONVERSATION_HISTORY');
    }

    public function test_client_can_retrieve_own_document_by_lao_number(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'in_progress', now(), [
            'lao_number' => 'LAO-26-009',
            'action_type' => 'Review',
            'sent_to' => 'PRIVATE_DESTINATION',
            'particulars' => 'PRIVATE_PARTICULARS',
            'rejection_reason' => 'PRIVATE_REJECTION_REASON',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'Ano status ng document LAO-26-009?',
            'history' => [
                ['role' => 'user', 'content' => 'PRIVATE_CONVERSATION_HISTORY'],
            ],
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Document LAO-26-009 is In Progress. Assigned action type: Review.',
            ])
            ->assertDontSee('PRIVATE_DESTINATION')
            ->assertDontSee('PRIVATE_PARTICULARS')
            ->assertDontSee('PRIVATE_REJECTION_REASON')
            ->assertDontSee('PRIVATE_CONVERSATION_HISTORY');
    }

    public function test_client_cannot_retrieve_another_clients_document_even_with_its_lao_number(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(29, 'completed', now(), [
            'lao_number' => 'LAO-26-029',
            'particulars' => 'OTHER_CLIENT_PRIVATE_PARTICULARS',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'Kumusta ang status ng document LAO-26-029?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'I couldn’t find an authorized document with that LAO number.',
            ])
            ->assertDontSee('Completed')
            ->assertDontSee('OTHER_CLIENT_PRIVATE_PARTICULARS');
    }

    public function test_lao_lookup_with_no_authorized_match_returns_generic_response(): void
    {
        $this->actingAsClient(17);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'Can you tell me the status for LAO-26-404?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'I couldn’t find an authorized document with that LAO number.',
            ]);
    }

    public function test_lao_lookup_failure_returns_a_safe_response_without_query_details(): void
    {
        $this->actingAsClient(17);
        Log::shouldReceive('error')
            ->once()
            ->with('Client chatbot document lookup failed.', [
                'exception_class' => \RuntimeException::class,
            ]);

        $documents = Mockery::mock(ClientDocumentLookupService::class);
        $documents->shouldReceive('statusByLaoNumberResult')
            ->once()
            ->andThrow(new \RuntimeException('PRIVATE_QUERY_AND_LAO_DETAILS'));
        $this->app->instance(ClientDocumentLookupService::class, $documents);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of LAO-26-009?',
        ])
            ->assertStatus(503)
            ->assertExactJson([
                'reply' => 'I can’t retrieve your document information right now. Please try again later or use the Documents page.',
            ])
            ->assertDontSee('PRIVATE_QUERY_AND_LAO_DETAILS')
            ->assertDontSee('LAO-26-009');
    }

    public function test_lao_lookup_returns_only_fields_allowed_for_each_status(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now(), [
            'lao_number' => 'LAO-26-101',
            'action_type' => 'PRIVATE_ACTION',
            'sent_to' => 'PRIVATE_DESTINATION',
        ]);
        $this->insertDocument(17, 'in_progress', now(), [
            'lao_number' => 'LAO-26-102',
            'action_type' => 'Review',
            'sent_to' => 'PRIVATE_DESTINATION',
        ]);
        $this->insertDocument(17, 'outgoing', now(), [
            'lao_number' => 'LAO-26-103',
            'sent_to' => 'Records Office',
            'sent_date' => '2026-09-10',
            'action_type' => 'PRIVATE_ACTION',
        ]);
        $this->insertDocument(17, 'completed', now(), [
            'lao_number' => 'LAO-26-104',
            'action_type' => 'PRIVATE_ACTION',
            'sent_to' => 'PRIVATE_DESTINATION',
        ]);
        $this->insertDocument(17, 'rejected', now(), [
            'lao_number' => 'LAO-26-105',
            'rejection_reason' => 'PRIVATE_REJECTION_REASON',
        ]);
        $this->insertDocument(17, 'archived', now(), [
            'lao_number' => 'LAO-26-106',
            'action_type' => 'PRIVATE_ACTION',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $expectedReplies = [
            'LAO-26-101' => 'Document LAO-26-101 is Pending.',
            'LAO-26-102' => 'Document LAO-26-102 is In Progress. Assigned action type: Review.',
            'LAO-26-103' => 'Document LAO-26-103 is Outgoing. Recorded destination: Records Office. Date sent: September 10, 2026.',
            'LAO-26-104' => 'Document LAO-26-104 is Completed.',
            'LAO-26-105' => 'Document LAO-26-105 is Rejected. Please view its authorized rejection reason in the Documents page.',
            'LAO-26-106' => 'Document LAO-26-106 is Archived — Retained for Records.',
        ];

        foreach ($expectedReplies as $laoNumber => $expectedReply) {
            $this->postJson('/chatbot/message', [
                'message' => "What is the status of {$laoNumber}?",
            ])
                ->assertOk()
                ->assertExactJson(['reply' => $expectedReply]);
        }
    }

    public function test_chatbot_requires_authentication_and_client_role(): void
    {
        $this->postJson('/chatbot/message', [
            'message' => 'What does Pending mean?',
        ])->assertUnauthorized();

        $this->actingAsUser(17, hasClientRole: false);

        $this->postJson('/chatbot/message', [
            'message' => 'What does Pending mean?',
        ])->assertForbidden();
    }

    public function test_approved_english_tagalog_and_taglish_questions_reach_openai_as_asked(): void
    {
        $this->actingAsClient(17);
        $questions = [
            'What is the Legal Affairs Office?',
            'What is legal office?',
            'What is the Messages page?',
            'ano ibig sabihin ng outgoing?',
            'Ano ang transmittal?',
            'Paano mag-upload ng document sa LexTrack?',
            'Compare Pending and Completed statuses.',
            'What email should I use to sign in?',
            'What email notifications does LexTrack send?',
            'How do I distinguish a new submission from a revision request?',
            'Can a Pending submission be accepted?',
        ];
        $prompts = [];

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')
            ->times(count($questions))
            ->andReturn(true);
        $assistant->shouldReceive('prompt')
            ->times(count($questions))
            ->withArgs(function (mixed ...$arguments) use (&$prompts): bool {
                $arguments = array_values($arguments);
                $prompts[] = (string) ($arguments[0] ?? '');

                return in_array(Lab::OpenAI, $arguments, true)
                    && in_array('gpt-5-mini', $arguments, true);
            })
            ->andReturn(new AgentResponse(
                'test-invocation',
                'A concise response grounded in the approved guide.',
                new Usage,
                new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
            ));
        $this->app->instance(LexTrackAssistant::class, $assistant);

        foreach ($questions as $message) {
            $this->postJson('/chatbot/message', [
                'message' => $message,
                'history' => [
                    ['role' => 'user', 'content' => 'PRIVATE_CONVERSATION_HISTORY'],
                ],
            ])
                ->assertOk()
                ->assertExactJson([
                    'reply' => 'A concise response grounded in the approved guide.',
                ])
                ->assertDontSee('PRIVATE_CONVERSATION_HISTORY');
        }

        foreach ($questions as $index => $question) {
            $this->assertStringContainsString($question, $prompts[$index]);
            $this->assertStringNotContainsString('PRIVATE_CONVERSATION_HISTORY', $prompts[$index]);
        }
    }

    public function test_general_follow_up_uses_only_previous_general_exchanges(): void
    {
        $this->actingAsClient(17);
        $prompts = [];

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')
            ->twice()
            ->andReturn(true);
        $assistant->shouldReceive('prompt')
            ->twice()
            ->withArgs(function (mixed ...$arguments) use (&$prompts): bool {
                $arguments = array_values($arguments);
                $prompts[] = (string) ($arguments[0] ?? '');

                return in_array(Lab::OpenAI, $arguments, true);
            })
            ->andReturn(new AgentResponse(
                'test-invocation',
                'A transmittal accompanies a document submission.',
                new Usage,
                new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
            ));
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'Ano ang transmittal?'])->assertOk();
        $this->postJson('/chatbot/message', ['message' => 'How do I upload it?'])->assertOk();

        $this->assertSame('Ano ang transmittal?', $prompts[0]);
        $this->assertStringContainsString('Ano ang transmittal?', $prompts[1]);
        $this->assertStringContainsString('How do I upload it?', $prompts[1]);
    }

    public function test_sensitive_identifiers_in_an_assistant_reply_are_not_reused_as_history(): void
    {
        $this->actingAsClient(17);
        $prompts = [];

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')->twice()->andReturn(true);
        $assistant->shouldReceive('prompt')
            ->twice()
            ->withArgs(function (mixed ...$arguments) use (&$prompts): bool {
                $arguments = array_values($arguments);
                $prompts[] = (string) ($arguments[0] ?? '');

                return in_array(Lab::OpenAI, $arguments, true);
            })
            ->andReturn(
                new AgentResponse(
                    'test-invocation-1',
                    'Example identifier: LAO-26-009.',
                    new Usage,
                    new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
                ),
                new AgentResponse(
                    'test-invocation-2',
                    'A general answer from the approved guide.',
                    new Usage,
                    new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
                ),
            );
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'What does Outgoing mean?'])->assertOk();
        $this->postJson('/chatbot/message', ['message' => 'Tell me more about that status.'])->assertOk();

        $this->assertSame('What does Outgoing mean?', $prompts[0]);
        $this->assertSame('Tell me more about that status.', $prompts[1]);
        $this->assertStringNotContainsString('LAO-26-009', $prompts[1]);
    }

    public function test_ambiguous_private_question_offers_only_owned_documents_then_rechecks_selection(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'in_progress', now(), [
            'lao_number' => 'LAO-26-301',
            'document_type' => 'Contract',
            'action_type' => 'Review',
            'particulars' => 'PRIVATE_SELECTOR_PARTICULARS',
            'sent_to' => 'PRIVATE_SELECTOR_DESTINATION',
            'rejection_reason' => 'PRIVATE_SELECTOR_REJECTION_REASON',
        ]);
        $this->insertDocument(29, 'completed', now(), [
            'lao_number' => 'LAO-26-999',
            'document_type' => 'Other client record',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is the status of this document?',
        ])
            ->assertOk()
            ->assertSee('LAO-26-301')
            ->assertDontSee('LAO-26-999')
            ->assertDontSee('PRIVATE_SELECTOR_PARTICULARS')
            ->assertDontSee('PRIVATE_SELECTOR_DESTINATION')
            ->assertDontSee('PRIVATE_SELECTOR_REJECTION_REASON');

        $this->postJson('/chatbot/message', [
            'message' => 'document 1',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'Your selected document is In Progress. Assigned action type: Review.',
            ])
            ->assertDontSee('LAO-26-999');
    }

    public function test_latest_document_identity_question_stays_in_laravel_and_offers_authorized_choices(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now(), [
            'document_type' => 'Certification',
            'lao_number' => null,
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'Which document did I submit most recently?',
        ])
            ->assertOk()
            ->assertSee('LAO number not assigned')
            ->assertSee('Certification')
            ->assertDontSee('Pending');
    }

    public function test_session_document_selection_cannot_access_another_clients_record(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(29, 'completed', now(), ['lao_number' => 'LAO-26-999']);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->withSession(['chatbot.document_choices' => [
            'user_id' => '17',
            'document_ids' => [1],
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]])
            ->postJson('/chatbot/message', ['message' => 'document 1'])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'I couldn’t find an authorized document with that LAO number.',
            ])
            ->assertDontSee('Completed')
            ->assertDontSee('LAO-26-999');
    }

    public function test_expired_or_other_user_document_choices_are_not_reused(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'pending', now(), ['lao_number' => 'LAO-26-017']);
        $this->insertDocument(29, 'completed', now(), ['lao_number' => 'LAO-26-999']);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->withSession(['chatbot.document_choices' => [
            'user_id' => '29',
            'document_ids' => [2],
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]])
            ->postJson('/chatbot/message', ['message' => 'Document 1'])
            ->assertOk()
            ->assertSee('LAO-26-017')
            ->assertDontSee('LAO-26-999')
            ->assertDontSee('Completed');

        $this->withSession(['chatbot.document_choices' => [
            'user_id' => '17',
            'document_ids' => [2],
            'expires_at' => now()->subMinute()->getTimestamp(),
        ]])
            ->postJson('/chatbot/message', ['message' => 'Document 1'])
            ->assertOk()
            ->assertSee('LAO-26-017')
            ->assertDontSee('LAO-26-999')
            ->assertDontSee('Completed');
    }

    public function test_question_intent_and_document_reference_are_resolved_separately(): void
    {
        $router = app(ChatbotIntentRouter::class);

        $this->assertSame(
            'workflow_explanation',
            $router->detectQuestionIntent('Pano ba siya magiging In Progress?'),
        );
        $this->assertSame(
            ['type' => 'contextual'],
            $router->resolveDocumentReference(
                'Pano ba siya magiging In Progress?',
                hasPrivateDocumentContext: true,
            ),
        );
        $this->assertSame(
            'status_lookup',
            $router->detectQuestionIntent('What is the status of my latest document?'),
        );
        $this->assertSame(
            ['type' => 'latest'],
            $router->resolveDocumentReference('What is the status of my latest document?'),
        );
        $this->assertSame(
            'yes_no_comparison',
            $router->detectQuestionIntent('Is Pending further along than In Progress?'),
        );
        $this->assertSame(
            'yes_no_comparison',
            $router->detectQuestionIntent('Magkaiba ba ang Pending at In Progress?'),
        );
    }

    public function test_tagalog_and_taglish_latest_status_requests_use_laravel_only(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'outgoing', now());

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        foreach ([
            'Ano ang status ng pinakabagong dokumento ko?',
            'Kumusta ang status ng latest submission ko?',
            'Kumusta na yung latest submission ko?',
            'Any update sa latest document ko?',
            'Did my latest submission get approved?',
            'Na-approve na ba yung latest submission ko?',
        ] as $message) {
            $this->postJson('/chatbot/message', ['message' => $message])
                ->assertOk()
                ->assertExactJson([
                    'reply' => 'Your latest submitted document is currently Outgoing.',
                ]);
        }
    }

    public function test_status_comparison_is_scoped_to_the_authenticated_client_and_never_calls_openai(): void
    {
        $this->actingAsClient(17);
        $this->insertDocument(17, 'completed', now()->subDay(), ['lao_number' => 'LAO-26-401']);
        $this->insertDocument(17, 'outgoing', now(), ['lao_number' => 'LAO-26-402']);
        $this->insertDocument(29, 'rejected', now(), ['lao_number' => 'LAO-26-999']);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', ['message' => 'Compare my latest documents'])
            ->assertOk()
            ->assertSee('LAO-26-402 — Outgoing')
            ->assertSee('LAO-26-401 — Completed')
            ->assertDontSee('LAO-26-999')
            ->assertDontSee('Rejected');

        $this->postJson('/chatbot/message', ['message' => 'Ihambing ang mga huling isinumite ko'])
            ->assertOk()
            ->assertSee('LAO-26-402 — Outgoing')
            ->assertSee('LAO-26-401 — Completed')
            ->assertDontSee('LAO-26-999');

        $this->postJson('/chatbot/message', [
            'message' => 'Compare the statuses of LAO-26-401 and LAO-26-402',
        ])
            ->assertOk()
            ->assertSee('LAO-26-401 — Completed')
            ->assertSee('LAO-26-402 — Outgoing')
            ->assertDontSee('PRIVATE');

        $this->postJson('/chatbot/message', [
            'message' => 'I-compare ang LAO-26-401 at LAO-26-402',
        ])
            ->assertOk()
            ->assertSee('LAO-26-401 — Completed')
            ->assertSee('LAO-26-402 — Outgoing');

        $this->postJson('/chatbot/message', [
            'message' => 'Compare LAO-26-401 and LAO-26-999',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'I couldn’t find an authorized document with that LAO number.',
            ])
            ->assertDontSee('Rejected');
    }

    public function test_private_follow_up_after_document_inquiry_never_reaches_openai(): void
    {
        $this->actingAsClient(17);
        $prompts = [];
        $this->insertDocument(17, 'outgoing', now(), [
            'lao_number' => 'LAO-26-501',
            'sent_to' => 'PRIVATE_DESTINATION',
        ]);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')->twice()->andReturn(true);
        $assistant->shouldReceive('prompt')
            ->twice()
            ->withArgs(function (mixed ...$arguments) use (&$prompts): bool {
                $arguments = array_values($arguments);
                $prompts[] = (string) ($arguments[0] ?? '');

                return in_array(Lab::OpenAI, $arguments, true);
            })
            ->andReturn(new AgentResponse(
                'test-invocation',
                'A transmittal accompanies a document submission.',
                new Usage,
                new Meta(Lab::OpenAI->value, 'gpt-5-mini'),
            ));
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'What is a transmittal?',
            'history' => [['role' => 'user', 'content' => 'PRIVATE_CLIENT_DOCUMENT_HISTORY']],
        ])->assertOk();

        $this->postJson('/chatbot/message', ['message' => 'Where is my document?'])->assertOk();
        $this->postJson('/chatbot/message', ['message' => 'And where was it sent?'])
            ->assertOk()
            ->assertDontSee('PRIVATE_DESTINATION');
        $this->postJson('/chatbot/message', ['message' => 'What is LexTrack?'])->assertOk();

        $this->assertSame('What is a transmittal?', $prompts[0]);
        $this->assertSame('What is LexTrack?', $prompts[1]);
        $this->assertStringNotContainsString('Where is my document?', $prompts[1]);
        $this->assertStringNotContainsString('PRIVATE_DESTINATION', $prompts[1]);
    }

    public function test_unsupported_file_and_record_actions_never_reach_openai(): void
    {
        $this->actingAsClient(17);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldNotReceive('hasApprovedKnowledgeBase');
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        foreach ([
            'Please read the contents of my uploaded document.',
            'Please approve my submission.',
            'What email address is on my account?',
            'Ano ang dapat kong gawin sa kaso ko?',
        ] as $message) {
            $this->postJson('/chatbot/message', ['message' => $message])
                ->assertOk()
                ->assertSee('cannot provide personal legal advice');
        }
    }

    public function test_general_question_does_not_call_openai_while_knowledge_base_is_unapproved(): void
    {
        $this->actingAsClient(17);

        $assistant = Mockery::mock(LexTrackAssistant::class);
        $assistant->shouldReceive('hasApprovedKnowledgeBase')
            ->once()
            ->andReturn(false);
        $assistant->shouldNotReceive('prompt');
        $this->app->instance(LexTrackAssistant::class, $assistant);

        $this->postJson('/chatbot/message', [
            'message' => 'How do I submit a document?',
        ])
            ->assertOk()
            ->assertExactJson([
                'reply' => 'General answers are unavailable until the LexTrack knowledge base is approved. Please check the Client Portal guide or contact the Legal Affairs Office.',
            ]);
    }

    private function actingAsClient(int $id, bool $active = true): void
    {
        $this->actingAsUser($id, hasClientRole: true, status: $active ? User::DEFAULT_STATUS : 'Inactive');
    }

    private function actingAsUser(
        int $id,
        bool $hasClientRole = true,
        string $status = User::DEFAULT_STATUS,
    ): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill([
            'id' => $id,
            'status' => $status,
        ]);
        $user->shouldReceive('hasRole')
            ->with('Client')
            ->andReturn($hasClientRole);

        $this->actingAs($user);
    }

    private function insertOfficeSender(int $id, string $role, string $status = User::DEFAULT_STATUS): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'status' => $status,
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $id,
        ]);
    }

    private function insertDocument(
        int $userId,
        string $status,
        \Illuminate\Support\Carbon $createdAt,
        array $overrides = [],
    ): void {
        DB::table('documents')->insert(array_merge([
            'user_id' => $userId,
            'status' => $status,
            'action_type' => null,
            'sent_to' => null,
            'sent_date' => null,
            'particulars' => null,
            'lao_number' => null,
            'rejection_reason' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ], $overrides));
    }

    private function insertConversation(int $documentId, int $participantId): int
    {
        $now = now();
        $conversationId = (int) DB::table('conversations')->insertGetId([
            'document_id' => $documentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('conversation_participants')->insert([
            'conversation_id' => $conversationId,
            'user_id' => $participantId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $conversationId;
    }

    private function insertMessage(int $conversationId, int $senderId, string $body): int
    {
        $now = now();

        return (int) DB::table('messages')->insertGetId([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
