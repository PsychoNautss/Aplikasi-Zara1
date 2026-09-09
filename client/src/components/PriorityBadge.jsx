const STYLES = {
  low: "bg-slate-100 text-slate-600",
  medium: "bg-amber-100 text-amber-700",
  high: "bg-rose-100 text-rose-700",
};

const LABELS = { low: "Rendah", medium: "Sedang", high: "Tinggi" };

export default function PriorityBadge({ priority }) {
  return (
    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STYLES[priority] || STYLES.medium}`}>
      {LABELS[priority] || priority}
    </span>
  );
}
