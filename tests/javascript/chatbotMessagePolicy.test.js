import test from 'node:test'
import assert from 'node:assert/strict'
import {
  canSendChatbotMessage,
  hasProhibitedChatbotTerm
} from '../../resources/js/utils/chatbotMessagePolicy.js'

const prohibitedTerms = [
  'fuck',
  'fucker',
  'motherfucker',
  'putang ina',
  'putangina',
  'potangina',
  'tangina',
  'taena',
  'puta',
  'pota',
  'shuta',
  'shet',
  'gago',
  'gagu',
  'gaga',
  'ass',
  'asshole',
  'bitch',
  'dick',
  'tarantado',
  'son of a bitch',
  'panget',
  'bobo',
  'engot',
  'baliw',
  'bwesit',
  'bwisit',
  'bwiset',
  'bweset',
  'buset',
  'mama mo',
  'shit',
  'what the hell',
  'what the hel',
  'wat the hell',
  'wat the hel',
  'what the heck',
  'wat the heck'
]

test('detects a prohibited term and punctuation or spacing obfuscation', () => {
  assert.equal(hasProhibitedChatbotTerm('fuck', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('f.u c.k', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('p u t a n g  i n a', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('shitttttttttttttt', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('putsngins tslsgs', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('kingina', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('8080 k ba', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('sabihin mo sa mama baliw ka', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('What the hell?', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('wat the hel', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('WHAT...THE...HECK!', prohibitedTerms), true)
  assert.equal(hasProhibitedChatbotTerm('wat   the   heck', prohibitedTerms), true)

  for (const message of ['bwesit', 'bwisit', 'bwiset', 'bweset', 'buset', 'Bwesit!']) {
    assert.equal(hasProhibitedChatbotTerm(message, prohibitedTerms), true, message)
    assert.equal(canSendChatbotMessage(message, false, prohibitedTerms), false, message)
  }

  for (const message of [
    'Tangina', 'Gago', 'Mama mo', '8080', 'Puta', 'Pota', 'Potangina',
    'Putangina', 'taena', 'shuta', 'shet', 'gagu', 'gaga', 'T@ena',
    'Fuck', 'Shit', 'Ass', '@ss', 'Asshole', 'Bitch', 'Dick', 'tarantado',
    'son of a bitch', 'motherfucker', 'fucker'
  ]) {
    assert.equal(hasProhibitedChatbotTerm(message, prohibitedTerms), true, message)
  }
})

test('keeps normal LexTrack terms valid', () => {
  for (const message of ['request', 'original', 'clearance', 'transmittal', 'thanks', 'okay', 'reputation', 'as requested']) {
    assert.equal(hasProhibitedChatbotTerm(message, prohibitedTerms), false)
    assert.equal(canSendChatbotMessage(message, false, prohibitedTerms), true)
  }

  assert.equal(hasProhibitedChatbotTerm('kalapati', prohibitedTerms), false)
})

test('disables sending only for empty, loading, or prohibited input', () => {
  assert.equal(canSendChatbotMessage('', false, prohibitedTerms), false)
  assert.equal(canSendChatbotMessage('   ', false, prohibitedTerms), false)
  assert.equal(canSendChatbotMessage('What is my document status?', true, prohibitedTerms), false)
  assert.equal(canSendChatbotMessage('fuck', false, prohibitedTerms), false)
  assert.equal(canSendChatbotMessage('What is my document status?', false, prohibitedTerms), true)
})
