# Song Manager

Frontend to manage music data for the Rondo App.

## Tech Stack

- **Backend**: PHP / Slim Framework
- **Frontend**: Vue 3 + TypeScript + Vite
- **CSS**: Bootstrap 5, Font Awesome 4

## Development

Install frontend dependencies:

```bash
npm install
```

Start the Vite dev server (proxies `/api` to `localhost`):

```bash
npm run dev
```

## Build

Build the frontend bundle to `src/frontend/dist/`:

```bash
npm run build
```

The production entry point is `src/index.html`

## Deployment

Use the GitHub Deploy Action to deploy to production.
