import PriorityBadge from "./PriorityBadge.jsx";

function formatDate(dateStr) {
  if (!dateStr) return null;
  return new Date(dateStr).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
}

function isOverdue(dueDate, status) {
  if (!dueDate || status === "done") return false;
  return new Date(dueDate) < new Date(new Date().toDateString());
}

export default function TaskItem({ task, onToggle, onDelete }) {
  const overdue = isOverdue(task.due_date, task.status);

  return (
    <li className="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3">
      <input
        type="checkbox"
        checked={task.status === "done"}
        onChange={() => onToggle(task)}
        className="mt-1 h-4 w-4 accent-brand-500"
      />
      <div className="flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className={`font-medium ${task.status === "done" ? "text-slate-400 line-through" : "text-slate-800"}`}>
            {task.title}
          </span>
          <PriorityBadge priority={task.priority} />
          {task.due_date && (
            <span className={`text-xs ${overdue ? "font-semibold text-rose-600" : "text-slate-500"}`}>
              Tenggat: {formatDate(task.due_date)}
              {overdue && " (terlambat)"}
            </span>
          )}
          {task.assignee_name && (
            <span className="text-xs text-slate-400">· ditugaskan ke {task.assignee_name}</span>
          )}
        </div>
        {task.description && <p className="mt-1 text-sm text-slate-500">{task.description}</p>}
      </div>
      <button
        onClick={() => onDelete(task)}
        className="text-xs text-slate-400 hover:text-rose-600"
        title="Hapus tugas"
      >
        Hapus
      </button>
    </li>
  );
}
