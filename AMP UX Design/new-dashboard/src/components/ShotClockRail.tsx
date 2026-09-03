import { Zap, Clock, ArrowRight } from 'lucide-react';
import type { Project, UrgencyLevel } from '../data/mockProjects';

const URGENCY_CONFIG: Record<UrgencyLevel, {
  border: string;
  bar: string;
  badge: string;
  badgeText: string;
  days: string;
  topStripe: string;
  label: string;
}> = {
  critical: {
    border: 'border-[#CF222E]',
    bar: 'bg-[#CF222E]',
    badge: 'bg-[#FFEBE9] text-[#CF222E]',
    badgeText: 'CRITICAL',
    days: 'text-[#CF222E]',
    topStripe: 'bg-[#CF222E]',
    label: 'CRITICAL',
  },
  warning: {
    border: 'border-[#9A6700]',
    bar: 'bg-[#9A6700]',
    badge: 'bg-[#FFF8C5] text-[#9A6700]',
    badgeText: 'WARNING',
    days: 'text-[#9A6700]',
    topStripe: 'bg-[#D4A72C]',
    label: 'WARNING',
  },
  ok: {
    border: 'border-[#D1D9E0]',
    bar: 'bg-[#1A7F37]',
    badge: 'bg-[#DAFBE1] text-[#1A7F37]',
    badgeText: 'ON TRACK',
    days: 'text-[#1A7F37]',
    topStripe: 'bg-[#1A7F37]',
    label: 'ON TRACK',
  },
  hold: {
    border: 'border-[#D1D9E0]',
    bar: 'bg-[#8C959F]',
    badge: 'bg-[#EEF1F4] text-[#656D76]',
    badgeText: 'ON HOLD',
    days: 'text-[#8C959F]',
    topStripe: 'bg-[#8C959F]',
    label: 'ON HOLD',
  },
};

function getProgressPercent(daysLeft: number | null, urgency: UrgencyLevel): number {
  if (daysLeft === null) return 0;
  if (urgency === 'critical') return Math.max(5, (daysLeft / 3) * 30);
  if (urgency === 'warning') return Math.max(30, (daysLeft / 7) * 60);
  return Math.min(95, 60 + daysLeft * 2);
}

interface ShotClockCardProps {
  project: Project;
}

function ShotClockCard({ project }: ShotClockCardProps) {
  const cfg = URGENCY_CONFIG[project.urgency];
  const progress = getProgressPercent(project.daysLeft, project.urgency);

  return (
    <div
      className={`
        bg-white border rounded-xl overflow-hidden flex flex-col min-w-[220px] flex-1
        hover:shadow-md transition-all cursor-pointer shadow-sm
        ${cfg.border}
      `}
    >
      {/* Top color stripe */}
      <div className={`h-1 w-full ${cfg.topStripe}`} />

      <div className="p-4 flex flex-col gap-3">
        {/* Header row */}
        <div className="flex items-start justify-between">
          <div>
            <div className="text-[11px] text-[#8C959F] font-semibold tracking-wide uppercase">
              {project.id}
            </div>
            <div className="text-[#1A1F2E] font-semibold mt-0.5">{project.siteName}</div>
            <div className="text-xs text-[#656D76]">
              {project.municipality}, {project.state}
            </div>
          </div>
          <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${cfg.badge}`}>
            {cfg.label}
          </span>
        </div>

        {/* Clock label */}
        <div className="flex items-center gap-1.5 text-xs text-[#656D76]">
          <Clock size={12} />
          {project.shotClockLabel}
        </div>

        {/* Progress bar + days */}
        <div className="space-y-1.5">
          <div className="h-1.5 bg-[#EEF1F4] rounded-full overflow-hidden">
            <div
              className={`h-full rounded-full transition-all ${cfg.bar}`}
              style={{ width: `${progress}%` }}
            />
          </div>
          <div className="flex justify-between items-center">
            <span className={`text-2xl font-bold tabular-nums ${cfg.days}`}>
              {project.daysLeft === null
                ? '—'
                : project.daysLeft === 0
                ? 'TODAY'
                : `${project.daysLeft}d`}
            </span>
            <span className="text-[10px] text-[#8C959F]">days remaining</span>
          </div>
        </div>
      </div>
    </div>
  );
}

interface ShotClockRailProps {
  projects: Project[];
}

export function ShotClockRail({ projects }: ShotClockRailProps) {
  const urgentProjects = projects
    .filter(p => p.urgency !== 'hold' && p.daysLeft !== null)
    .sort((a, b) => (a.daysLeft ?? 99) - (b.daysLeft ?? 99))
    .slice(0, 4);

  const criticalCount = urgentProjects.filter(p => p.urgency === 'critical').length;

  return (
    <section>
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <Zap size={16} className="text-[#CF222E]" />
          <span className="text-sm font-semibold text-[#1A1F2E]">Shot Clocks</span>
          {criticalCount > 0 && (
            <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#FFEBE9] text-[#CF222E]">
              {criticalCount} CRITICAL
            </span>
          )}
        </div>
        <button className="flex items-center gap-1 text-xs text-[#656D76] hover:text-[#0969DA] transition-colors font-medium">
          View All <ArrowRight size={12} />
        </button>
      </div>

      <div className="flex gap-3 overflow-x-auto pb-1">
        {urgentProjects.map(project => (
          <ShotClockCard key={project.id} project={project} />
        ))}
      </div>
    </section>
  );
}
