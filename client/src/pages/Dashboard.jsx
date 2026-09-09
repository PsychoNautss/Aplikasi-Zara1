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
    <div className="mx-auto max-w-5xl px-4 py-8">
      {/* Header Banner */}
      <div className="mb-8 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-600 p-8 text-white shadow-lg">
        <h1 className="mb-2 text-3xl font-extrabold tracking-tight">Daftar Tugasmu ✨</h1>
        <p className="text-brand-100 opacity-90 max-w-2xl text-lg">
          Kelompokkan tugas pribadi atau tim ke dalam beberapa daftar, lalu pantau progresnya dengan mudah.
        </p>
      </div>

      {/* Create Form */}
      <div className="mb-10 rounded-xl bg-white p-6 shadow-md border border-slate-100">
        <h2 className="mb-4 text-lg font-semibold text-slate-700">Buat Daftar Baru</h2>
        <form onSubmit={handleCreate} className="flex flex-col sm:flex-row gap-3">
          <input
            type="text"
            placeholder="Misal: Proyek Skripsi, Belanja Bulanan..."
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="flex-1 rounded-lg border-2 border-slate-200 px-4 py-3 text-slate-700 transition-colors focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200"
          />
          <button
            type="submit"
            disabled={creating || !name.trim()}
            className="rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white transition-all hover:bg-brand-700 hover:shadow-lg disabled:opacity-50 disabled:hover:shadow-none active:scale-95"
          >
            {creating ? "Membuat..." : "+ Buat Daftar"}
          </button>
        </form>
        {error && <p className="mt-3 text-sm text-rose-500 font-medium">{error}</p>}
      </div>

      {/* Lists Area */}
      <h2 className="mb-4 text-xl font-bold text-slate-800">Daftar Aktif</h2>
      
      {lists === null && (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-32 rounded-xl bg-slate-200 animate-pulse"></div>
          ))}
        </div>
      )}

      {lists?.length === 0 && (
        <div className="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 py-12 text-center transition-colors hover:border-brand-300 hover:bg-brand-50">
          <span className="text-4xl">📂</span>
          <p className="mt-4 text-lg font-medium text-slate-600">Belum ada daftar tugas</p>
          <p className="text-sm text-slate-500">Mulai dengan membuat daftar pertamamu di atas!</p>
        </div>
      )}

      <ul className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {lists?.map((list) => (
          <li key={list.id} className="group">
            <Link
              to={`/lists/${list.id}`}
              className="block h-full rounded-xl border border-slate-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-brand-400 hover:shadow-xl relative overflow-hidden"
            >
              <div className="absolute top-0 left-0 w-1 h-full bg-brand-500 transition-all duration-300 group-hover:w-2"></div>
              <div className="mb-4 flex items-start justify-between">
                <span className="font-bold text-lg text-slate-800 line-clamp-2">{list.name}</span>
                <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${list.isOwner ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'}`}>
                  {list.isOwner ? "Pemilik" : "Kolaborator"}
                </span>
              </div>
              <div className="mt-auto pt-4">
                <ProgressBar progress={list.progress} doneCount={list.doneCount} taskCount={list.taskCount} />
              </div>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
