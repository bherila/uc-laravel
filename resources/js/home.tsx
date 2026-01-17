import './bootstrap';
import { createRoot } from 'react-dom/client';
import React from 'react';

function Home() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <div className="max-w-3xl">
        <h1 className="text-3xl font-semibold">You are probably in the wrong place!</h1>

        <p className="mt-4 text-base">
          This is the UC Admin Portal. It&rsquo;s a mirage. If you want to buy wine, go to{' '}
          <a
            href="https://www.undergroundcellar.com"
            className="font-medium underline underline-offset-4 hover:text-primary transition-colors"
          >
            undergroundcellar.com
          </a>.
        </p>
      </div>
    </div>
  );
}

const homeElement = document.getElementById('home');
if (homeElement) {
  createRoot(homeElement).render(<Home />);
}
