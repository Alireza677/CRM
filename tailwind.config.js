// tailwind.config.js
import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["IRANSans", ...defaultTheme.fontFamily.sans],
      },
      colors: {
        primary: {
          DEFAULT: "#A7C7E7", // آبی پاستلی
          dark: "#7AAAD1",
          light: "#D6E9FA",
        },
        secondary: {
          DEFAULT: "#A8E6CF", // سبز نعنایی
          dark: "#7AC9A4",
          light: "#D2F7E6",
        },
        danger: {
          DEFAULT: "#FFB3BA", // صورتی ملایم
          dark: "#FF8A95",
          light: "#FFD6D9",
        },
        warning: {
          DEFAULT: "#FFF3B0", // زرد ملایم
          dark: "#FDE68A",
          light: "#FFF9D9",
        },
        neutral: {
          100: "#F5F5F5", // پس‌زمینه خیلی روشن
          300: "#E0E0E0",
          600: "#666666",
          900: "#333333",
        },
      },
    },
  },
  plugins: [forms],
};
