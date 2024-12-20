'use client'
import { Board } from './components/Board';
import React from 'react';

export default function Home() {

  return (
    <main className="flex min-h-screen flex-col items-center justify-center p-12 bg-gray-800">
      <div className="relative w-[600px] h-[600px] bg-gray-200 rounded-xl shadow-md p-4 ">
        <Board />
      </div>
    </main>
  );
}