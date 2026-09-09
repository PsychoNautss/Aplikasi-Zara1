import { useState } from "react";

const EMPTY = { title: "", description: "", priority: "medium", dueDate: "" };

export default function TaskForm({ onSubmit, submitLabel = "Tambah Tugas" }) {
  const [form, setForm] = useState(EMPTY);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.title.trim()) return;
    setSubmitting(true);
    setError("");
    try {
      await onSubmit(form);
      setForm(EMPTY);
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-slate-200 bg-white p-4">
      {error && <p className="mb-2 text-sm text-rose-600">{error}</p>}
      <div className="flex flex-col gap-2 sm:flex-row">
        <input
          type="text"
          placeholder="Judul tugas..."
          value={form.title}
          onChange={(e) => setForm({ ...form, title: e.target.value })}
          className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
        <select
          value={form.priority}
          onChange={(e) => setForm({ ...form, priority: e.target.value })}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        >
          <option value="low">Prioritas Rendah</option>
          <option value="medium">Prioritas Sedang</option>
          <option value="high">Prioritas Tinggi</option>
        </select>
        <input
          type="date"
          value={form.dueDate}
          onChange={(e) => setForm({ ...form, dueDate: e.target.value })}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
      </div>
      <textarea
        placeholder="Deskripsi (opsional)"
        value={form.description}
        onChange={(e) => setForm({ ...form, description: e.target.value })}
        rows={2}
        className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
      />
      <button
        type="submit"
        disabled={submitting || !form.title.trim()}
        className="mt-2 rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50"
      >
        {submitting ? "Menyimpan..." : submitLabel}
      </button>
    </form>
  );
}
