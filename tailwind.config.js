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

    // hero gradient variants
    'from-blue-950/95', 'via-blue-950/85', 'to-transparent',
    'from-purple-900/90', 'via-purple-900/70',
    'from-indigo-950/95', 'via-indigo-900/70',
    'bg-gradient-to-r', 'bg-gradient-to-br',
    'bg-cyan-400', 'bg-purple-400', 'blur-3xl',

    // hero section dynamic utilities
    'opacity-0', 'opacity-100', 'transition-opacity', 'duration-1000',
    'z-10', 'z-20', 'z-30',
    'from-purple-900/90', 'via-purple-900/70',
    'from-indigo-950/95', 'via-indigo-900/70',
    'bg-gradient-to-br',
    'flex', 'items-center', 'justify-center',
    'max-w-7xl', 'mx-auto', 'h-full', 'px-8', 'md:px-16',
    'text-white', 'drop-shadow-2xl', 'text-blue-50',
    'leading-tight', 'leading-relaxed',

    // How It Works section additions
    'bg-gradient-to-b',                          // for background gradients in cards
    'from-cyan-500', 'to-cyan-600',              // gradient step 1
    'from-blue-500', 'to-blue-600',              // gradient step 2
    'from-indigo-500', 'to-indigo-600',          // gradient step 3
    'from-purple-500', 'to-purple-600',          // gradient step 4

    'from-cyan-50', 'to-cyan-100',               // card soft bg step 1
    'from-blue-50', 'to-blue-100',               // card soft bg step 2
    'from-indigo-50', 'to-indigo-100',           // card soft bg step 3
    'from-purple-50', 'to-purple-100',           // card soft bg step 4

    'ring-cyan-500', 'ring-blue-500', 'ring-indigo-500', 'ring-purple-500', // ring highlights

    'translate-y-10', 'translate-y-0',           // for fade-up animations
    'scale-0', 'scale-100',                      // for badge pop-in animations
    'opacity-0', 'opacity-100',                  // already present but keep for safety
    'duration-700', 'transition-all',            // animation utilities
    'hover:shadow-xl', 'hover:border-gray-200',  // hover effects on cards

    // AI Product Finder section
    'bg-gradient-to-b', 'from-gray-50', 'to-white',
    'from-purple-500', 'to-cyan-500',
    'from-purple-600', 'via-blue-600', 'to-cyan-600',
    'from-cyan-500', 'to-blue-600', 'hover:from-cyan-600', 'hover:to-blue-700',
    'from-cyan-100', 'to-blue-100', 'from-cyan-500/20', 'to-blue-600/20',
    'from-purple-500', 'to-pink-600', 'hover:from-purple-600', 'hover:to-pink-700',
    'from-purple-100', 'to-pink-100', 'from-purple-500/20', 'to-pink-600/20',
    'rounded-3xl', 'rounded-2xl', 'rounded-full',
    'border-gray-100', 'hover:border-cyan-200', 'hover:border-purple-200',
    'group-hover/img:border-cyan-300', 'group-hover/img:border-purple-300',
    'shadow-lg', 'shadow-2xl', 'hover:shadow-2xl',
    'opacity-30', 'opacity-50', 'group-hover:opacity-50',
    'blur-3xl', 'blur-xl',
    'transition-all', 'transition-opacity', 'transition-transform', 'duration-300',
    'group-hover/img:opacity-20', 'group-hover/img:opacity-100',
    'group-hover/btn:translate-x-1',
    'text-transparent', 'bg-clip-text',

    // layout for AI Product Finder
    'grid', 'gap-6', 'gap-8',
    'md:grid-cols-2', 'lg:grid-cols-2',
    'w-full', 'max-w-7xl', 'mx-auto',
    'p-8', 'py-20', 'px-4',

    // key features
    'bg-gradient-to-b', 'from-white', 'to-gray-50',
    'from-cyan-500', 'to-blue-600',
    'from-purple-500', 'to-pink-600',
    'from-indigo-500', 'to-purple-600',
    'from-blue-500', 'to-cyan-600',
    'from-cyan-100', 'to-blue-100',
    'from-purple-100', 'to-pink-100',
    'from-indigo-100', 'to-purple-100',
    'from-blue-100', 'to-cyan-100',
    'bg-gradient-to-br', 'blur-3xl',
    'rounded-2xl', 'transition-opacity',
    'opacity-0', 'opacity-100', 'translate-y-6', 'translate-y-0',
    'grid', 'md:grid-cols-2', 'gap-6',
    'group-hover:w-full',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

