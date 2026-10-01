export const CHATBOT_VALIDATION_ERROR = 'Please use respectful language. Offensive or prohibited words are not allowed.'

export function normalizeChatbotMessage(value) {
  let message = String(value ?? '').toLocaleLowerCase().trim()

  message = message
    .normalize('NFKC')
    .replace(/[0134578]/g, (character) => ({
      0: 'o',
      1: 'i',
      3: 'e',
      4: 'a',
      5: 's',
      7: 't',
      8: 'b'
    })[character])

  message = message.replace(/[^\p{L}\p{N}]+/gu, ' ')
  message = message.replace(/([\p{L}\p{N}])\1+/gu, '$1')

  return message.replace(/\s+/g, ' ').trim()
}

export function hasProhibitedChatbotTerm(value, prohibitedTerms = []) {
  const normalizedMessage = normalizeChatbotMessage(value)

  if (!normalizedMessage) return false

  return prohibitedTerms.some((term) => {
    const normalizedTerm = normalizeChatbotMessage(term)

    if (!normalizedTerm) return false

    const termPattern = [...normalizedTerm]
      .map(escapeRegExp)
      .join('[^\\p{L}\\p{N}]*')

    return new RegExp(`(?<![\\p{L}\\p{N}])${termPattern}(?![\\p{L}\\p{N}])`, 'u').test(normalizedMessage)
      || matchesTypoVariant(normalizedMessage, normalizedTerm)
  })
}

export function canSendChatbotMessage(value, loading, prohibitedTerms = []) {
  return !loading
    && String(value ?? '').trim() !== ''
    && !hasProhibitedChatbotTerm(value, prohibitedTerms)
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

function matchesTypoVariant(message, term) {
  const compactTerm = term.replace(/[^\p{L}\p{N}]+/gu, '')

  if (compactTerm.length < 5) return false

  const maxDistance = compactTerm.length >= 7 ? 2 : 1

  return message.split(/\s+/u).some((token) => token.length >= 4
    && levenshtein(token, compactTerm) <= maxDistance)
}

function levenshtein(left, right) {
  const previous = Array.from({ length: right.length + 1 }, (_, index) => index)

  for (let leftIndex = 0; leftIndex < left.length; leftIndex += 1) {
    let diagonal = previous[0]
    previous[0] = leftIndex + 1

    for (let rightIndex = 0; rightIndex < right.length; rightIndex += 1) {
      const nextDiagonal = previous[rightIndex + 1]
      previous[rightIndex + 1] = left[leftIndex] === right[rightIndex]
        ? diagonal
        : Math.min(diagonal + 1, previous[rightIndex + 1] + 1, previous[rightIndex] + 1)
      diagonal = nextDiagonal
    }
  }

  return previous[right.length]
}
