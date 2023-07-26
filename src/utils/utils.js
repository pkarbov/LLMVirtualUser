export function GenRandomId(length = 5) {
    return Math.random()
        .toString(36)
        .replace(/[^a-z]+/g, '')
        .slice(0, length)
}

export function isDarkMode() {
    const bodyDataTheme = document.body.getAttribute('data-themes')
    return bodyDataTheme.startsWith('light')
        ? false
        : bodyDataTheme.startsWith('dark')
            ? true
            : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
}
