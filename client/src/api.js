const TOKEN_KEY = "zara_token";

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  if (token) localStorage.setItem(TOKEN_KEY, token);
  else localStorage.removeItem(TOKEN_KEY);
}

async function request(path, { method = "GET", body } = {}) {
  const headers = { "Content-Type": "application/json" };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`/api${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) return null;

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error || "Terjadi kesalahan tak terduga.");
  }
  return data;
}

export const api = {
  register: (payload) => request("/auth/register", { method: "POST", body: payload }),
  login: (payload) => request("/auth/login", { method: "POST", body: payload }),
  me: () => request("/auth/me"),

  getLists: () => request("/lists"),
  createList: (payload) => request("/lists", { method: "POST", body: payload }),
  getList: (id) => request(`/lists/${id}`),
  updateList: (id, payload) => request(`/lists/${id}`, { method: "PATCH", body: payload }),
  deleteList: (id) => request(`/lists/${id}`, { method: "DELETE" }),
  addMember: (id, email) => request(`/lists/${id}/members`, { method: "POST", body: { email } }),
  removeMember: (id, userId) => request(`/lists/${id}/members/${userId}`, { method: "DELETE" }),

  getTasks: (listId) => request(`/tasks/list/${listId}`),
  createTask: (listId, payload) => request(`/tasks/list/${listId}`, { method: "POST", body: payload }),
  updateTask: (id, payload) => request(`/tasks/${id}`, { method: "PATCH", body: payload }),
  deleteTask: (id) => request(`/tasks/${id}`, { method: "DELETE" }),

  getAdminUsers: () => request("/admin/users"),
  getAdminUserLists: (userId) => request(`/admin/users/${userId}/lists`),
  getAdminUserTasks: (userId) => request(`/admin/users/${userId}/tasks`),
  createAdminTask: (userId, payload) =>
    request(`/admin/users/${userId}/tasks`, { method: "POST", body: payload }),
  deleteAdminTask: (id) => request(`/admin/tasks/${id}`, { method: "DELETE" }),
};
