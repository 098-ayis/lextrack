export async function getCurrentUser() {
    try {
        const response = await fetch('/api/user', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
            },
        })

        if (!response.ok) {
            return null
        }

        const data = await response.json()

        return data.user
    } catch (error) {
        console.error('Failed to get current user:', error)
        return null
    }
}