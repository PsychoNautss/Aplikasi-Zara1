import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext.jsx";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setSubmitting(true);
    setError("");
    try {
      await login(email, password);
      navigate("/");
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto mt-16 max-w-sm px-4">
      <h1 className="mb-1 text-2xl font-bold text-slate-800">Masuk ke Zara</h1>
      <p className="mb-6 text-sm text-slate-500">Kelola tugas pribadi dan tim di satu tempat.</p>
      <form onSubmit={handleSubmit} className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5">
        {error && <p className="text-sm text-rose-600">{error}</p>}
        <input
          type="email"
          required
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
        <input
          type="password"
          required
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
        <button
          type="submit"
          disabled={submitting}
          className="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50"
        >
          {submitting ? "Memproses..." : "Masuk"}
        </button>
      </form>
      <p className="mt-4 text-sm text-slate-500">
        Belum punya akun?{" "}
        <Link to="/register" className="text-brand-600 hover:underline">
          Daftar di sini
        </Link>
      </p>
    </div>
  );
}
