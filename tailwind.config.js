module.exports = {
    content: [
        "./templates/**/*.html.twig", // Scan Twig templates
        "./assets/**/*.js",           // Scan JS files in assets
        "./src/**/*.{js,ts,vue}",     // Scan JS, TypeScript, and Vue components
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};