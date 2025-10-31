/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.{html,twig}",
    "./assets/**/*.{js,jsx,ts,tsx,vue}",
    "./src/**/*.php",
    "./public/**/*.html",
  ],
  safelist: [
    // existing
    'hidden', 'block', 'flex',
    'sm:block', 'md:block', 'md:flex', 'lg:block', 'lg:flex',
    'relative', 'absolute',
    'z-50', 'z-40', 'z-[60]',
    'bg-gradient-to-r', 'from-indigo-950', 'to-blue-900', 'via-slate-800',

    // new classes for AI Filter section
    'from-blue-600', 'via-blue-700', 'to-cyan-600',
    'hover:from-blue-700', 'hover:to-cyan-700',
    'rounded-t-2xl', 'rounded-b-2xl',
    'border-blue-500', 'border-green-500', 'border-gray-200',
    'bg-blue-50', 'bg-green-50', 'bg-gray-50',
    'hover:border-blue-300', 'hover:border-green-300',
    'hover:bg-gray-50',

    // utilities for layout and spacing
    'min-w-[180px]', 'space-x-3', 'space-y-3',
    'overflow-hidden', 'cursor-pointer',
    'text-blue-100', 'text-blue-200', 'text-blue-600',
    'text-green-600', 'text-cyan-600', 'text-purple-600',
    'text-gray-900', 'text-gray-500',

    // ✅ new dynamic JS classes
    'bg-green-500', 'bg-blue-500',
    'bg-green-100', 'bg-blue-100',
    'border-green-500', 'border-blue-500',
    'text-white',

    // custom “option” states for JS toggles
    'use-option', 'budget-option', 'active-option',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

