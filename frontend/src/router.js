const routes = [];

export function addRoute(path, render) {
  const keys = [];
  const pattern = path
    .replace(/:[^/]+/g, (part) => {
      keys.push(part.slice(1));
      return '([^/]+)';
    })
    .replace(/\//g, '\\/');

  routes.push({ path, render, keys, regex: new RegExp(`^${pattern}$`) });
}

export function navigate(path) {
  if (window.location.pathname === path) return;
  window.history.pushState({}, '', path);
  renderRoute();
}

export async function renderRoute() {
  const app = document.getElementById('app');
  const path = window.location.pathname;
  const matched = routes.find((route) => route.regex.test(path)) || routes.find((route) => route.path === '/');
  const values = matched.regex.exec(path)?.slice(1) || [];
  const params = Object.fromEntries(matched.keys.map((key, index) => [key, decodeURIComponent(values[index])]));

  app.classList.add('is-changing');
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await matched.render(app, params);
  app.classList.remove('is-changing');
}

export function bindRouter() {
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-link]');
    if (!link) return;
    event.preventDefault();
    navigate(link.getAttribute('href'));
  });

  window.addEventListener('popstate', renderRoute);
}
