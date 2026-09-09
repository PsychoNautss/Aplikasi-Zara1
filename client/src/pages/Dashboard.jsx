import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../api.js";
import ProgressBar from "../components/ProgressBar.jsx";

export default function Dashboard() {
  const [lists, setLists] = useState(null);
  const [name, setName] = useState("");
  const [error, setError] = useState("");
  const [creating, setCreating] = useState(false);

  function loadLists() {
    api.getLists().then((data) => setLists(data.lists));
  }

  useEffect(loadLists, []);

  async function handleCreate(e) {
    e.preventDefault();
    if (!name.trim()) return;
    setCreating(true);
    setError("");
    try {
      await api.createList({ name });
      setName("");
      loadLists();
    } catch (err) {
      setError(err.message);
    } finally {
      setCreating(false);
    }
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <h1 className="mb-1 text-2xl font-bold text-slate-800">Daftar Tugasmu</h1>
      <p className="mb-6 text-sm text-slate-500">
        Kelompokkan tugas pribadi atau tim ke dalam beberapa daftar, lalu pantau progresnya.
      </p>

      <form onSubmit={handleCreate} className="mb-6 flex gap-2">
        <input
          type="text"
          placeholder="Nama daftar baru, misal: Proyek Skripsi"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
        <button
          type="submit"
          disabled={creating || !name.trim()}
          className="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50"
        >
          Buat Daftar
        </button>
      </form>
      {error && <p className="mb-4 text-sm text-rose-600">{error}</p>}

      {lists === null && <p className="text-sm text-slate-400">Memuat...</p>}
      {lists?.length === 0 && (
        <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
          Belum ada daftar tugas. Buat daftar pertamamu di atas.
        </p>
      )}

      <ul className="flex flex-col gap-3">
        {lists?.map((list) => (
          <li key={list.id}>
            <Link
              to={`/lists/${list.id}`}
              className="block rounded-lg border border-slate-200 bg-white p-4 hover:border-brand-300 hover:shadow-sm"
            >
              <div className="mb-2 flex items-center justify-between">
                <span className="font-semibold text-slate-800">{list.name}</span>
                <span className="text-xs text-slate-400">{list.isOwner ? "Pemilik" : "Kolaborator"}</span>
              </div>
              <ProgressBar progress={list.progress} doneCount={list.doneCount} taskCount={list.taskCount} />
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
