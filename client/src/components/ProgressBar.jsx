export default function ProgressBar({ progress, doneCount, taskCount }) {
  return (
    <div>
      <div className="flex items-center justify-between text-xs text-slate-500">
        <span>
          {doneCount}/{taskCount} selesai
        </span>
        <span>{progress}%</span>
      </div>
      <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div
          className="h-full rounded-full bg-brand-500 transition-all"
          style={{ width: `${progress}%` }}
        />
      </div>
    </div>
  );
}
