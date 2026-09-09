import { useEffect, useState } from "react";
import { api } from "../api.js";
import PriorityBadge from "../components/PriorityBadge.jsx";

export default function AdminPanel() {
  const [users, setUsers] = useState(null);
  const [selectedUser, setSelectedUser] = useState(null);
  const [userLists, setUserLists] = useState([]);
  const [tasks, setTasks] = useState([]);
  const [form, setForm] = useState({ listId: "", title: "", priority: "medium", dueDate: "" });
  const [error, setError] = useState("");

  useEffect(() => {
    loadUsers();
  }, []);

  function loadUsers() {
    api.getAdminUsers().then((data) => setUsers(data.users));
  }

  function selectUser(user) {
    setSelectedUser(user);
    setError("");
    api.getAdminUserLists(user.id).then((data) => {
      setUserLists(data.lists);
      setForm((f) => ({ ...f, listId: data.lists[0]?.id || "" }));
    });
    loadTasks(user.id);
  }

  function loadTasks(userId) {
    api.getAdminUserTasks(userId).then((data) => setTasks(data.tasks));
  }

  async function handleAddTask(e) {
    e.preventDefault();
    if (!form.title.trim() || !form.listId) return;
    setError("");
    try {
      await api.createAdminTask(selectedUser.id, form);
      setForm((f) => ({ ...f, title: "", dueDate: "" }));
      loadTasks(selectedUser.id);
      loadUsers();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDeleteTask(task) {
    if (!confirm(`Hapus tugas "${task.title}" milik ${selectedUser.name}?`)) return;
    await api.deleteAdminTask(task.id);
    loadTasks(selectedUser.id);
    loadUsers();
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-8">
      <h1 className="mb-1 text-2xl font-bold text-slate-800">Panel Admin</h1>
      <p className="mb-6 text-sm text-slate-500">
        Kelola tugas seluruh pengguna — tambahkan atau hapus tugas atas nama mereka.
      </p>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div className="rounded-lg border border-slate-200 bg-white p-4 md:col-span-1">
          <h2 className="mb-3 text-sm font-semibold text-slate-700">Pengguna</h2>
          <ul className="flex flex-col gap-1">
            {users?.map((u) => (
              <li key={u.id}>
                <button
                  onClick={() => selectUser(u)}
                  className={`w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-100 ${
                    selectedUser?.id === u.id ? "bg-brand-50 text-brand-700" : "text-slate-700"
                  }`}
                >
                  <div className="font-medium">
                    {u.name} {u.role === "admin" && <span className="text-xs text-brand-500">(admin)</span>}
                  </div>
                  <div className="text-xs text-slate-400">
                    {u.email} · {u.task_count} tugas
                  </div>
                </button>
              </li>
            ))}
          </ul>
        </div>

        <div className="md:col-span-2">
          {!selectedUser && (
            <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
              Pilih pengguna untuk melihat dan mengelola tugasnya.
            </p>
          )}

          {selectedUser && (
            <>
              <div className="mb-4 rounded-lg border border-slate-200 bg-white p-4">
                <h2 className="mb-2 text-sm font-semibold text-slate-700">
                  Tambah Tugas untuk {selectedUser.name}
                </h2>
                {userLists.length === 0 ? (
                  <p className="text-sm text-slate-400">
                    Pengguna ini belum punya daftar tugas apa pun, jadi admin belum bisa menambahkan tugas.
                  </p>
                ) : (
                  <form onSubmit={handleAddTask} className="flex flex-col gap-2">
                    {error && <p className="text-sm text-rose-600">{error}</p>}
                    <div className="flex flex-col gap-2 sm:flex-row">
                      <select
                        value={form.listId}
                        onChange={(e) => setForm({ ...form, listId: e.target.value })}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                      >
                        {userLists.map((l) => (
                          <option key={l.id} value={l.id}>
                            {l.name}
                          </option>
                        ))}
                      </select>
                      <input
                        type="text"
                        placeholder="Judul tugas..."
                        value={form.title}
                        onChange={(e) => setForm({ ...form, title: e.target.value })}
                        className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm"
                      />
                    </div>
                    <div className="flex gap-2">
                      <select
                        value={form.priority}
                        onChange={(e) => setForm({ ...form, priority: e.target.value })}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                      >
                        <option value="low">Prioritas Rendah</option>
                        <option value="medium">Prioritas Sedang</option>
                        <option value="high">Prioritas Tinggi</option>
                      </select>
                      <input
                        type="date"
                        value={form.dueDate}
                        onChange={(e) => setForm({ ...form, dueDate: e.target.value })}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                      />
                      <button
                        type="submit"
                        className="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600"
                      >
                        Tambah
                      </button>
                    </div>
                  </form>
                )}
              </div>

              <div className="rounded-lg border border-slate-200 bg-white p-4">
                <h2 className="mb-2 text-sm font-semibold text-slate-700">Semua Tugas {selectedUser.name}</h2>
                {tasks.length === 0 && <p className="text-sm text-slate-400">Belum ada tugas.</p>}
                <ul className="flex flex-col gap-2">
                  {tasks.map((task) => (
                    <li
                      key={task.id}
                      className="flex items-center justify-between rounded-md border border-slate-100 p-2 text-sm"
                    >
                      <div>
                        <span className={task.status === "done" ? "text-slate-400 line-through" : "text-slate-800"}>
                          {task.title}
                        </span>{" "}
                        <PriorityBadge priority={task.priority} />
                        <span className="ml-2 text-xs text-slate-400">di {task.list_name}</span>
                      </div>
                      <button onClick={() => handleDeleteTask(task)} className="text-xs text-slate-400 hover:text-rose-600">
                        Hapus
                      </button>
                    </li>
                  ))}
                </ul>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
