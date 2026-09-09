import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext.jsx";

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  function handleLogout() {
    logout();
    navigate("/login");
  }

  return (
    <header className="border-b border-slate-200 bg-white">
      <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <Link to="/" className="text-lg font-bold text-brand-600">
          Zara
        </Link>
        {user && (
          <nav className="flex items-center gap-4 text-sm">
            <Link to="/" className="text-slate-600 hover:text-brand-600">
              Daftar Tugas
            </Link>
            {user.role === "admin" && (
              <Link to="/admin" className="text-slate-600 hover:text-brand-600">
                Panel Admin
              </Link>
            )}
            <span className="text-slate-400">|</span>
            <span className="text-slate-600">{user.name}</span>
            <button
              onClick={handleLogout}
              className="rounded-md bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200"
            >
              Keluar
            </button>
          </nav>
        )}
      </div>
    </header>
  );
}
