import { ShotClockRail } from './ShotClockRail';
import { ActionPanel } from './ActionPanel';
import { PortfolioChart } from './PortfolioChart';
import { ProjectTable } from './ProjectTable';
import { MOCK_PROJECTS, MOCK_ACTIONS, PORTFOLIO_STATS } from '../data/mockProjects';

export function Dashboard() {
  return (
    <main className="flex-1 overflow-y-auto p-6 space-y-6 bg-[#F6F8FA]">
      {/* Page title */}
      <div>
        <h1 className="text-lg font-semibold text-[#1A1F2E]">Project Dashboard</h1>
        <p className="text-sm text-[#656D76] mt-0.5">
          Thursday, June 5, 2026 · {PORTFOLIO_STATS.total} active projects
        </p>
      </div>

      {/* Zone 1 — Shot Clocks */}
      <ShotClockRail projects={MOCK_PROJECTS} />

      {/* Zone 2 — Action + Portfolio */}
      <div className="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4">
        <ActionPanel items={MOCK_ACTIONS} />
        <PortfolioChart total={PORTFOLIO_STATS.total} />
      </div>

      {/* Zone 3 — Project Table */}
      <ProjectTable projects={MOCK_PROJECTS} />
    </main>
  );
}
