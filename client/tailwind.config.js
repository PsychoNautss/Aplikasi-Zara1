/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        brand: {
          50: "#f2f6ff",
          100: "#e3ebff",
          200: "#c3d2ff",
          300: "#9db3ff",
          400: "#6e88ff",
          500: "#4a63f5",
          600: "#3747d1",
          700: "#2c37a8",
          800: "#252f85",
          900: "#212a68",
        },
      },
    },
  },
  plugins: [],
};
