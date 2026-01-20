import './bootstrap';
import { createRoot } from 'react-dom/client';
import React from 'react';
import { Button } from '@/components/ui/button';

function Home() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4 py-8">
      <div className="max-w-3xl">
        <h1 className="text-4xl font-bold tracking-tight mb-4">You are probably in the wrong place!</h1>

        <p className="text-xl text-muted-foreground mb-10">
          This is the UC Admin Portal. If you want to buy wine, please visit our main site.
        </p>

        <div className="flex flex-col items-center gap-4">
          <Button asChild size="lg" className="bg-green-600 hover:bg-green-700 text-white font-bold h-14 px-8 text-lg rounded-full shadow-lg transition-transform hover:scale-105">
            <a href="https://www.undergroundcellar.com">
              Go to undergroundcellar.com
            </a>
          </Button>

          <Button asChild variant="ghost" className="text-muted-foreground hover:text-foreground">
            <a href="/login">
              Login as employee
            </a>
          </Button>
        </div>
      </div>
    </div>
  );
}

const homeElement = document.getElementById('home');
if (homeElement) {
  createRoot(homeElement).render(<Home />);
}
