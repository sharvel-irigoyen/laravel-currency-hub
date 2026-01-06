/** @type {import('@maizzle/framework').Config} */
export default {
  build: {
    templates: {
      source: 'src/templates',
      destination: {
        path: '../../resources/views/emails',
        extension: 'blade.php',
      },
    },
  },
  inlineCSS: true,
}
