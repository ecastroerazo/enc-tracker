
module.exports = {
  content: [
    "./test.html",
    "./*.php",
    "./**/*.php",
    "./assets/js/**/*.js",
    "./assets/js/**/*.jsx",
    "./assets/js/**/*.ts",
    "./assets/js/**/*.tsx",
    "./components/**/*.{js,jsx,ts,tsx,php}",
    "./templates/**/*.php",
    "./parts/**/*.php",
    "./src/*.css",
    "./src/**/*.{css,scss,sass,less,styl,vue,html,twig,js,jsx,ts,tsx,php,json,md,mdx,yaml,yml,txt,svg,png,jpg,jpeg,gif,webp,avif,ico,eot,ttf,woff,woff2,otf,svgz,json5,xml,csv,tsv}",
    "../../plugins/**/*.php"
  ],
  safelist: [
    'max-w-lg', 'p-6', 'border', 'border-gray-300', 'rounded-lg', 'bg-white', 'shadow-sm',
    'mb-4', 'block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2', 
    'w-full', 'px-3', 'py-2', 'rounded-md', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500',
    'resize-none', 'mt-6', 'bg-blue-600', 'hover:bg-blue-700', 'text-white', 'transition', 'duration-200',
    'bg-red-100', 'border-red-300', 'text-red-700', 'bg-green-100', 'border-green-300', 'text-green-700', 'p-3'
  ],
  theme: {
    extend: {
      fontFamily: {
        lato: ["Lato", "sans-serif"],
      },
    },
  },
  plugins: [],
};
