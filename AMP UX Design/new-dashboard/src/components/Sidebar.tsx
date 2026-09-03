import {
  LayoutDashboard,
  FolderKanban,
  CheckSquare,
  FileText,
  Building2,
  CalendarDays,
  BarChart3,
  ChevronLeft,
} from 'lucide-react';

const NAV_ITEMS = [
  { icon: LayoutDashboard, label: 'Dashboard', active: true },
  { icon: FolderKanban, label: 'Projects', badge: 8 },
  { icon: CheckSquare, label: 'Tasks', badge: 5 },
  { icon: FileText, label: 'Forms' },
  { icon: Building2, label: 'Permits' },
  { icon: CalendarDays, label: 'Schedule' },
  { icon: BarChart3, label: 'Reports' },
];

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
}

export function Sidebar({ collapsed, onToggle }: SidebarProps) {
  return (
    <aside
      className={`
        flex flex-col bg-white border-r border-[#D1D9E0] transition-all duration-200
        ${collapsed ? 'w-14' : 'w-52'}
      `}
    >
      <nav className="flex-1 py-3 space-y-0.5 px-2">
        {NAV_ITEMS.map(({ icon: Icon, label, active, badge }) => (
          <button
            key={label}
            className={`
              w-full flex items-center gap-3 px-2.5 py-2 rounded-md text-sm transition-colors group
              ${active
                ? 'bg-[#0969DA1A] text-[#0969DA] font-semibold'
                : 'text-[#656D76] hover:text-[#1A1F2E] hover:bg-[#F6F8FA]'
              }
            `}
          >
            <Icon size={17} className="shrink-0" />
            {!collapsed && (
              <>
                <span className="flex-1 text-left">{label}</span>
                {badge != null && (
                  <span className={`
                    text-[11px] font-semibold px-1.5 py-0.5 rounded-full
                    ${active
                      ? 'bg-[#0969DA] text-white'
                      : 'bg-[#EEF1F4] text-[#656D76]'
                    }
                  `}>
                    {badge}
                  </span>
                )}
              </>
            )}
          </button>
        ))}
      </nav>

      {/* Collapse toggle */}
      <button
        onClick={onToggle}
        className="m-2 p-2 rounded-md hover:bg-[#F6F8FA] text-[#8C959F] hover:text-[#1A1F2E] transition-colors flex items-center justify-center border border-[#D1D9E0]"
        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
      >
        <ChevronLeft
          size={15}
          className={`transition-transform duration-200 ${collapsed ? 'rotate-180' : ''}`}
        />
      </button>
    </aside>
  );
}
