import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { api } from "../api.js";
import ProgressBar from "../components/ProgressBar.jsx";
import TaskForm from "../components/TaskForm.jsx";
import TaskItem from "../components/TaskItem.jsx";

export default function ListDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [tasks, setTasks] = useState(null);
  const [memberEmail, setMemberEmail] = useState("");
  const [memberError, setMemberError] = useState("");
  const [error, setError] = useState("");

  function loadAll() {
    api.getList(id).then(setData).catch((err) => setError(err.message));
    api.getTasks(id).then((data) => setTasks(data.tasks));
  }

  useEffect(loadAll, [id]);

  async function handleAddTask(form) {
    await api.createTask(id, form);
    loadAll();
  }

  async function handleToggle(task) {
    await api.updateTask(task.id, { status: task.status === "done" ? "pending" : "done" });
    loadAll();
  }

  async function handleDeleteTask(task) {
    if (!confirm(`Hapus tugas "${task.title}"?`)) return;
    await api.deleteTask(task.id);
    loadAll();
  }

  async function handleAddMember(e) {
    e.preventDefault();
    setMemberError("");
    try {
      await api.addMember(id, memberEmail);
      setMemberEmail("");
      loadAll();
    } catch (err) {
      setMemberError(err.message);
    }
  }

  async function handleRemoveMember(userId) {
    await api.removeMember(id, userId);
    loadAll();
  }

  async function handleDeleteList() {
    if (!confirm("Hapus daftar ini beserta semua tugas di dalamnya?")) return;
    await api.deleteList(id);
    navigate("/");
  }

  if (error) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-8">
        <p className="text-sm text-rose-600">{error}</p>
        <Link to="/" className="text-sm text-brand-600 hover:underline">
          Kembali ke daftar tugas
        </Link>
      </div>
    );
  }

  if (!data) return <div className="mx-auto max-w-3xl px-4 py-8 text-sm text-slate-400">Memuat...</div>;

  const { list, owner, members } = data;

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <Link to="/" className="text-sm text-brand-600 hover:underline">
        ← Semua daftar
      </Link>

      <div className="mt-2 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800">{list.name}</h1>
          <p className="text-sm text-slate-500">Pemilik: {owner.name}</p>
        </div>
        {list.isOwner && (
          <button onClick={handleDeleteList} className="text-sm text-rose-500 hover:underline">
            Hapus Daftar
          </button>
        )}
      </div>

      <div className="mt-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 className="mb-2 text-sm font-semibold text-slate-700">Progres Penyelesaian</h2>
        <ProgressBar progress={list.progress} doneCount={list.doneCount} taskCount={list.taskCount} />
      </div>

      {list.isOwner && (
        <div className="mt-4 rounded-lg border border-slate-200 bg-white p-4">
          <h2 className="mb-2 text-sm font-semibold text-slate-700">Kolaborator</h2>
          <ul className="mb-3 flex flex-wrap gap-2">
            {members.length === 0 && <li className="text-sm text-slate-400">Belum ada kolaborator.</li>}
            {members.map((m) => (
              <li
                key={m.id}
                className="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700"
              >
                {m.name}
                <button onClick={() => handleRemoveMember(m.id)} className="text-slate-400 hover:text-rose-600">
                  ×
                </button>
              </li>
            ))}
          </ul>
          <form onSubmit={handleAddMember} className="flex gap-2">
            <input
              type="email"
              required
              placeholder="Email kolaborator"
              value={memberEmail}
              onChange={(e) => setMemberEmail(e.target.value)}
              className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
            />
            <button
              type="submit"
              className="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
            >
              Undang
            </button>
          </form>
          {memberError && <p className="mt-2 text-sm text-rose-600">{memberError}</p>}
        </div>
      )}

      <div className="mt-6">
        <h2 className="mb-2 text-sm font-semibold text-slate-700">Tambah Tugas</h2>
        <TaskForm onSubmit={handleAddTask} />
      </div>

      <div className="mt-6">
        <h2 className="mb-2 text-sm font-semibold text-slate-700">Tugas</h2>
        {tasks?.length === 0 && (
          <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
            Belum ada tugas di daftar ini.
          </p>
        )}
        <ul className="flex flex-col gap-2">
          {tasks?.map((task) => (
            <TaskItem key={task.id} task={task} onToggle={handleToggle} onDelete={handleDeleteTask} />
          ))}
        </ul>
      </div>
    </div>
  );
}
