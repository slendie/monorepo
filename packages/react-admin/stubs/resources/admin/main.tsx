import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';
import { App } from './App';
import { readConfig } from './config';

const root = document.getElementById('app');

if (root) {
    readConfig(root);
    createRoot(root).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
