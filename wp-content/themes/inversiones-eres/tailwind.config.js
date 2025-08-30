
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
    "./src/**/*.{css,scss,sass,less,styl,vue,html,twig,js,jsx,ts,tsx,php,json,md,mdx,yaml,yml,txt,svg,png,jpg,jpeg,gif,webp,avif,ico,eot,ttf,woff,woff2,otf,svgz,json5,xml,csv,tsv}"
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
