import { Bell, Search, Settings, ChevronDown, Radio } from 'lucide-react';

interface TopBarProps {
  notificationCount: number;
}

export function TopBar({ notificationCount }: TopBarProps) {
  return (
    <header className="h-14 bg-white border-b border-[#D1D9E0] flex items-center px-4 gap-4 sticky top-0 z-50 shadow-sm">
      {/* Logo */}
      <div className="flex items-center gap-2 min-w-[200px]">
        <div className="w-8 h-8 rounded-lg bg-[#0969DA] flex items-center justify-center">
          <Radio size={16} className="text-white" />
        </div>
        <div className="leading-none">
          <span className="text-[#1A1F2E] font-semibold text-base tracking-wide">AMP</span>
        </div>
      </div>

      {/* Search */}
      <div className="flex-1 max-w-xl">
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-[#8C959F]" />
          <input
            type="text"
            placeholder="Search projects, sites, IDs..."
            className="w-full bg-[#F6F8FA] border border-[#D1D9E0] rounded-md pl-9 pr-4 py-1.5 text-sm text-[#1A1F2E] placeholder-[#8C959F] focus:outline-none focus:border-[#0969DA] focus:bg-white transition-colors"
          />
        </div>
      </div>

      <div className="flex items-center gap-1 ml-auto">
        {/* Notifications */}
        <button className="relative p-2 rounded-md hover:bg-[#F6F8FA] transition-colors">
          <Bell size={18} className="text-[#656D76]" />
          {notificationCount > 0 && (
            <span className="absolute top-1 right-1 w-4 h-4 bg-[#CF222E] rounded-full text-[10px] text-white font-bold flex items-center justify-center leading-none">
              {notificationCount}
            </span>
          )}
        </button>

        {/* Settings */}
        <button className="p-2 rounded-md hover:bg-[#F6F8FA] transition-colors">
          <Settings size={18} className="text-[#656D76]" />
        </button>

        {/* Divider */}
        <div className="w-px h-6 bg-[#D1D9E0] mx-1" />

        {/* User */}
        <button className="flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-[#F6F8FA] transition-colors">
          <div className="w-7 h-7 rounded-full bg-[#0969DA] flex items-center justify-center text-white text-xs font-bold">
            R
          </div>
          <span className="text-sm text-[#1A1F2E] font-medium">Richard</span>
          <ChevronDown size={14} className="text-[#8C959F]" />
        </button>
      </div>
    </header>
  );
}
