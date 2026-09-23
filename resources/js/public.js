import '../css/app.css'

const publicRoot = document.getElementById('public-app')
const isFilamentPage = /^\/(admin|client)(?:\/|$)/.test(window.location.pathname)
const platform = navigator.userAgentData?.platform || navigator.platform || navigator.userAgent

if (publicRoot && !isFilamentPage) {
    publicRoot.classList.toggle('is-windows', /Windows/i.test(platform))
}

if (publicRoot && !isFilamentPage) {
    Promise.all([
        import('vue'),
        import('./App.vue'),
        import('./router'),
    ]).then(([{ createApp }, { default: App }, { default: router }]) => {
        createApp(App)
            .use(router)
            .mount(publicRoot)
    })
}
