/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
      "./app/pages/**/*.{php, html, js}"
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography')
  ],
}

