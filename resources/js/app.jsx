import '../css/app.css'
import './bootstrap'
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

// createInertiaApp({
//   resolve: name => import(`./Pages/${name}.jsx`),
//   setup({ el, App, props }) {
//     createRoot(el).render(<App {...props} />)
//   },
// })
// Mapping manuel des composants
const pages = {
  'Parents/Auth/Login': () => import('./Pages/Parents/Auth/Login.jsx'),
  'Parents/Dashboard': () => import('./Pages/Parents/Dashboard.jsx'),
  // Ajoutez toutes vos autres pages ici
}

createInertiaApp({
  resolve: name => {
    if (pages[name]) {
      return pages[name]()
    }
    throw new Error(`Page "${name}" non trouvée dans le mapping`)
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
