import { useState } from 'react';
import {
  ArrowUpDown, Plus, ChevronDown, ChevronRight,
  CalendarDays, DollarSign, Clock, MapPin,
} from 'lucide-react';
import type { Project, UrgencyLevel, ProjectStatus } from '../data/mockProjects';

const STATUS_CONFIG: Partial<Record<ProjectStatus, { bg: string; text: string; dot: string }>> = {
  NTP:          { bg: 'bg-[#FFEBE9]', text: 'text-[#CF222E]', dot: 'bg-[#CF222E]' },
  Permitting:   { bg: 'bg-[#FFF8C5]', text: 'text-[#9A6700]', dot: 'bg-[#D4A72C]' },
  Zoning:       { bg: 'bg-[#FFF8C5]', text: 'text-[#9A6700]', dot: 'bg-[#D4A72C]' },
  Construction: { bg: 'bg-[#DDF4FF]', text: 'text-[#0969DA]', dot: 'bg-[#0969DA]' },
  Closeout:     { bg: 'bg-[#DAFBE1]', text: 'text-[#1A7F37]', dot: 'bg-[#1A7F37]' },
  'On Hold':    { bg: 'bg-[#EEF1F4]', text: 'text-[#656D76]', dot: 'bg-[#8C959F]' },
  Approved:     { bg: 'bg-[#DAFBE1]', text: 'text-[#1A7F37]', dot: 'bg-[#1A7F37]' },
  Submitted:    { bg: 'bg-[#FBEFFF]', text: 'text-[#8250DF]', dot: 'bg-[#8250DF]' },
  Revise:       { bg: 'bg-[#FFEBE9]', text: 'text-[#CF222E]', dot: 'bg-[#CF222E]' },
};

const URGENCY_CLOCK: Record<UrgencyLevel, string> = {
  critical: 'text-[#CF222E] font-bold',
  warning:  'text-[#9A6700] font-semibold',
  ok:       'text-[#656D76]',
  hold:     'text-[#8C959F]',
};

const FILTER_LABELS = ['All', 'Critical', 'Construction', 'Permitting', 'Closeout', 'On Hold'];

function StatusBadge({ status }: { status: ProjectStatus }) {
  const cfg = STATUS_CONFIG[status] ?? { bg: 'bg-[#EEF1F4]', text: 'text-[#656D76]', dot: 'bg-[#8C959F]' };
  return (
    <span className={`inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full ${cfg.bg} ${cfg.text}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${cfg.dot}`} />
      {status}
    </span>
  );
}

function ClockBadge({ project }: { project: Project }) {
  if (project.daysLeft === null) return <span className="text-xs text-[#8C959F]">—</span>;
  return (
    <span className={`text-xs tabular-nums ${URGENCY_CLOCK[project.urgency]}`}>
      {project.daysLeft === 0 ? '⚡ TODAY' : `${project.daysLeft}d`}
    </span>
  );
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatFee(fee: number): string {
  return `$${(fee / 1000).toFixed(0)}k`;
}

interface ProjectTableProps {
  projects: Project[];
}

export function ProjectTable({ projects }: ProjectTableProps) {
  const [activeFilter, setActiveFilter] = useState('All');
  const [sortKey, setSortKey] = useState<keyof Project>('daysLeft');

  const filtered = projects.filter(p => {
    if (activeFilter === 'All') return true;
    if (activeFilter === 'Critical') return p.urgency === 'critical';
    return p.status === activeFilter;
  });

  const sorted = [...filtered].sort((a, b) => {
    if (sortKey === 'daysLeft') {
      return (a.daysLeft ?? 999) - (b.daysLeft ?? 999);
    }
    return String(a[sortKey] ?? '').localeCompare(String(b[sortKey] ?? ''));
  });

  return (
    <section>
      {/* Controls row */}
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-1.5">
          {FILTER_LABELS.map(label => (
            <button
              key={label}
              onClick={() => setActiveFilter(label)}
              className={`
                px-3 py-1.5 rounded-full text-xs font-medium transition-colors border
                ${activeFilter === label
                  ? label === 'Critical'
                    ? 'bg-[#CF222E] text-white border-[#CF222E]'
                    : 'bg-[#0969DA] text-white border-[#0969DA]'
                  : 'bg-white text-[#656D76] border-[#D1D9E0] hover:border-[#8C959F] hover:text-[#1A1F2E]'
                }
              `}
            >
              {label}
            </button>
          ))}
        </div>

        <div className="flex items-center gap-2">
          <button className="flex items-center gap-1.5 text-xs text-[#656D76] hover:text-[#1A1F2E] border border-[#D1D9E0] px-3 py-1.5 rounded-md bg-white hover:bg-[#F6F8FA] transition-colors">
            <ArrowUpDown size={12} />
            Sort
            <ChevronDown size={12} />
          </button>
          <button className="flex items-center gap-1.5 text-xs bg-[#0969DA] hover:bg-[#0860C4] text-white px-3 py-1.5 rounded-md transition-colors font-semibold shadow-sm">
            <Plus size={13} />
            New Project
          </button>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white border border-[#D1D9E0] rounded-xl overflow-hidden shadow-sm">
        {/* Column headers */}
        <div className="grid grid-cols-[2fr_1.5fr_1.5fr_1fr_1fr_1fr_auto] gap-4 px-4 py-2.5 border-b border-[#EEF1F4] bg-[#F6F8FA]">
          {[
            { key: 'id',               label: 'Project / Site' },
            { key: 'status',           label: 'Status' },
            { key: 'towerOwner',       label: 'Tower Owner' },
            { key: 'constructionDate', label: 'Const. Date', icon: CalendarDays },
            { key: 'applicationFee',   label: 'Fee',         icon: DollarSign },
            { key: 'daysLeft',         label: 'Shot Clock',  icon: Clock },
          ].map(col => (
            <button
              key={col.key}
              onClick={() => setSortKey(col.key as keyof Project)}
              className={`
                flex items-center gap-1 text-[11px] font-semibold tracking-wider uppercase text-left transition-colors
                ${sortKey === col.key ? 'text-[#0969DA]' : 'text-[#8C959F] hover:text-[#1A1F2E]'}
              `}
            >
              {col.icon && <col.icon size={11} />}
              {col.label}
            </button>
          ))}
          <div />
        </div>

        {/* Rows */}
        <div className="divide-y divide-[#EEF1F4]">
          {sorted.map(project => (
            <div
              key={project.id}
              className="grid grid-cols-[2fr_1.5fr_1.5fr_1fr_1fr_1fr_auto] gap-4 px-4 py-3 hover:bg-[#F6F8FA] transition-colors cursor-pointer group items-center"
            >
              {/* Site */}
              <div className="min-w-0">
                <span className="text-sm font-semibold text-[#1A1F2E]">{project.siteName}</span>
                <div className="flex items-center gap-1 mt-0.5">
                  <MapPin size={10} className="text-[#8C959F]" />
                  <span className="text-[11px] text-[#656D76] truncate">
                    {project.id} · {project.municipality}, {project.state}
                  </span>
                </div>
              </div>

              {/* Status */}
              <div><StatusBadge status={project.status} /></div>

              {/* Tower Owner */}
              <div className="text-xs text-[#656D76] truncate" title={project.towerOwner}>
                {project.towerOwner}
              </div>

              {/* Construction Date */}
              <div className="text-xs text-[#1A1F2E] font-medium">
                {formatDate(project.constructionDate)}
              </div>

              {/* Fee */}
              <div className="text-xs text-[#1A1F2E] font-semibold tabular-nums">
                {formatFee(project.applicationFee)}
              </div>

              {/* Shot Clock */}
              <div><ClockBadge project={project} /></div>

              {/* Arrow */}
              <ChevronRight size={14} className="text-[#D1D9E0] group-hover:text-[#8C959F] transition-colors" />
            </div>
          ))}
        </div>

        {/* Footer */}
        <div className="px-4 py-2.5 border-t border-[#EEF1F4] bg-[#F6F8FA] flex items-center justify-between">
          <span className="text-xs text-[#656D76]">
            Showing {sorted.length} of {projects.length} projects
          </span>
          <div className="flex items-center gap-1">
            {[10, 25, 50].map(n => (
              <button
                key={n}
                className={`text-xs px-2.5 py-1 rounded-md border transition-colors ${
                  n === 10
                    ? 'bg-white border-[#D1D9E0] text-[#1A1F2E] font-semibold'
                    : 'border-transparent text-[#656D76] hover:text-[#1A1F2E]'
                }`}
              >
                {n}
              </button>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
