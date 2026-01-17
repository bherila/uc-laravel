import * as React from 'react';
import { useEffect, useState } from 'react';
import { Laptop, Moon, Sun } from 'lucide-react';

type NavbarProps = {
  authenticated: boolean;
  isAdmin: boolean;
};

type ThemeMode = 'system' | 'dark' | 'light';

function applyTheme(mode: ThemeMode) {
  const root = document.documentElement;
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const isDark = mode === 'dark' || (mode === 'system' && prefersDark);
  root.classList.toggle('dark', isDark);
}

export default function Navbar({ authenticated, isAdmin }: NavbarProps) {
  const [theme, setTheme] = useState<ThemeMode>(() => (localStorage.getItem('theme') as ThemeMode) || 'system');

  useEffect(() => {
    applyTheme(theme);
    localStorage.setItem('theme', theme);
  }, [theme]);

  useEffect(() => {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const onChange = () => {
      const saved = (localStorage.getItem('theme') as ThemeMode) || 'system';
      if (saved === 'system') applyTheme('system');
    };
    mq.addEventListener('change', onChange);
    return () => mq.removeEventListener('change', onChange);
  }, []);

  return (
    <nav className='mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4'>
      {/* Left: Branding + Main nav */}
      <div className='flex items-center gap-6'>
        <a href='/' className='select-none'>
          <h1 className='text-lg font-semibold tracking-tight'>UC Plugin</h1>
        </a>
        {authenticated && (
          <ul className='hidden md:flex items-center gap-4 text-sm'>
            <li><a className='hover:underline underline-offset-4' href='/shops'>Shops</a></li>
            {isAdmin && (
              <>
                <li><a className='hover:underline underline-offset-4' href='/admin/users'>Users</a></li>
              </>
            )}
          </ul>
        )}
      </div>

      {/* Right: Theme toggle + Auth */}
      <div className='flex items-center gap-3'>
        {/* Tri-state theme toggle */}
        <div className='inline-flex items-center overflow-hidden rounded-md border border-gray-200 dark:border-[#3E3E3A]'>
          <button
            type='button'
            onClick={() => setTheme('system')}
            className={`px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-[#1f1f1e] ${theme === 'system' ? 'bg-gray-100 dark:bg-[#262625]' : ''}`}
            title='System'
            aria-pressed={theme === 'system'}
          >
            <Laptop className='w-4 h-4' />
          </button>
          <button
            type='button'
            onClick={() => setTheme('dark')}
            className={`px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-[#1f1f1e] ${theme === 'dark' ? 'bg-gray-100 dark:bg-[#262625]' : ''}`}
            title='Dark'
            aria-pressed={theme === 'dark'}
          >
            <Moon className='w-4 h-4' />
          </button>
          <button
            type='button'
            onClick={() => setTheme('light')}
            className={`px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-[#1f1f1e] ${theme === 'light' ? 'bg-gray-100 dark:bg-[#262625]' : ''}`}
            title='Light'
            aria-pressed={theme === 'light'}
          >
            <Sun className='w-4 h-4' />
          </button>
        </div>

        {/* Auth buttons */}
        {!authenticated ? (
          <a
            href='/login'
            className='px-3 py-1.5 text-sm font-medium rounded-md border border-gray-200 dark:border-[#3E3E3A] hover:bg-gray-50 dark:hover:bg-[#1f1f1e] transition-colors'
          >
            Sign In
          </a>
        ) : (
          <form method='POST' action='/logout' className='inline'>
            <input type='hidden' name='_token' value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
            <button
              type='submit'
              className='px-3 py-1.5 text-sm font-medium rounded-md border border-gray-200 dark:border-[#3E3E3A] hover:bg-gray-50 dark:hover:bg-[#1f1f1e] transition-colors'
            >
              Sign Out
            </button>
          </form>
        )}
      </div>
    </nav>
  );
}
