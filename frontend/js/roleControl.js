// Use 'var' or window check to allow re-declaration across different files
if (typeof ROLE_API === 'undefined') {
    var ROLE_API = '/routes/api.php?action=me';
}

async function getCurrentUser() {
  try {
    const res = await fetch(ROLE_API, { credentials: 'include' });
    if (!res.ok) return null;

    const payload = await res.json();
    return payload.success ? payload.user : null;
  } catch {
    return null;
  }
}

function markAdminOnlyNav(role) {
  document.querySelectorAll('.admin-only').forEach((el) => {
    el.style.display = role === 'admin' ? '' : 'none';
  });
}

async function enforceUserOrAdmin() {
  const user = await getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
    return null;
  }
  markAdminOnlyNav(user.role);
  return user;
}

async function enforceAdminOnly() {
  const user = await getCurrentUser();
  if (!user || user.role !== 'admin') {
    window.location.href = 'index.html';
    return null;
  }
  markAdminOnlyNav(user.role);
  return user;
}

async function enforceGuestOrUserProtected() {
  const user = await getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
    return null;
  }
  markAdminOnlyNav(user.role);
  return user;
}

// FIX: Changed const to var and added a check to prevent "Already Declared" error
if (typeof API_BASE === 'undefined') {
    var API_BASE = '/routes/api.php';
}

function setupLogoutButton() {
  const logoutBtn = document.querySelector('.nav-logout');
  if (!logoutBtn) return;
  logoutBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    await fetch(`${API_BASE}?action=logout`);
    window.location.href = 'login.html';
  });
}

window.addEventListener('DOMContentLoaded', () => {
  setupLogoutButton();
});