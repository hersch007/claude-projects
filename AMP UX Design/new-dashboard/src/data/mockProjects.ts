export type UrgencyLevel = 'critical' | 'warning' | 'ok' | 'hold';

export type ProjectStatus =
  | 'NTP'
  | 'Permitting'
  | 'Zoning'
  | 'Construction'
  | 'Closeout'
  | 'On Hold'
  | 'Approved'
  | 'Submitted'
  | 'Revise';

export interface Project {
  id: string;
  siteName: string;
  address: string;
  municipality: string;
  state: string;
  status: ProjectStatus;
  urgency: UrgencyLevel;
  daysLeft: number | null;
  shotClockLabel: string;
  constructionDate: string | null;
  applicationFee: number;
  approvedFee: number | null;
  towerOwner: string;
  assignee: string;
  projectType: string;
  latitude: number;
  longitude: number;
  pendingForms: string[];
  pendingApprovals: string[];
  lastUpdated: string;
}

export interface ActionItem {
  id: string;
  projectId: string;
  siteName: string;
  type: 'form' | 'approval' | 'review' | 'brief';
  label: string;
  dueLabel: string;
  daysUntilDue: number;
  urgency: UrgencyLevel;
}

export const MOCK_PROJECTS: Project[] = [
  {
    id: 'TOWER-042',
    siteName: 'Maple Ridge',
    address: '195 HWY 118',
    municipality: 'Gallup',
    state: 'NM',
    status: 'NTP',
    urgency: 'critical',
    daysLeft: 2,
    shotClockLabel: 'NTP Deadline',
    constructionDate: '2026-06-18',
    applicationFee: 10500,
    approvedFee: 10500,
    towerOwner: 'American Tower Corporation',
    assignee: 'J. Smith',
    projectType: 'New Wireless',
    latitude: 35.533349,
    longitude: -108.636689,
    pendingForms: ['Form 7 — Site Certification', 'Form 12 — NTP Brief'],
    pendingApprovals: [],
    lastUpdated: '2026-06-03',
  },
  {
    id: 'TOWER-017',
    siteName: 'Westfield',
    address: '711-22 Tramway Pl NE',
    municipality: 'Albuquerque',
    state: 'NM',
    status: 'Permitting',
    urgency: 'warning',
    daysLeft: 5,
    shotClockLabel: 'Permit Expiration',
    constructionDate: '2026-07-02',
    applicationFee: 17500,
    approvedFee: 17500,
    towerOwner: 'Crown Castle',
    assignee: 'R. Lopez',
    projectType: 'Colocation',
    latitude: 35.1448,
    longitude: -106.6038,
    pendingForms: ['Form 3 — Permit Application'],
    pendingApprovals: ['Zoning Approval — City of Albuquerque'],
    lastUpdated: '2026-06-01',
  },
  {
    id: 'TOWER-091',
    siteName: 'Northview',
    address: '12 JB Ranch Rd',
    municipality: 'Tin Buck',
    state: 'NM',
    status: 'Construction',
    urgency: 'ok',
    daysLeft: 12,
    shotClockLabel: 'Construction Start',
    constructionDate: '2026-08-14',
    applicationFee: 10500,
    approvedFee: 10500,
    towerOwner: 'SBA Communications',
    assignee: 'T. Brown',
    projectType: 'New Wireless',
    latitude: 33.417,
    longitude: -104.523,
    pendingForms: [],
    pendingApprovals: [],
    lastUpdated: '2026-05-28',
  },
  {
    id: 'TOWER-055',
    siteName: 'Harbor Point',
    address: '875 US Highway 491 N',
    municipality: 'Gallup',
    state: 'NM',
    status: 'Zoning',
    urgency: 'warning',
    daysLeft: 8,
    shotClockLabel: 'Zoning Hearing',
    constructionDate: '2026-09-01',
    applicationFee: 10500,
    approvedFee: null,
    towerOwner: 'American Tower Corporation',
    assignee: 'J. Smith',
    projectType: 'Eligible Facility',
    latitude: 35.533,
    longitude: -108.74,
    pendingForms: ['Form 12 — Zoning Package'],
    pendingApprovals: ['County Commissioner Review'],
    lastUpdated: '2026-06-02',
  },
  {
    id: 'TOWER-006',
    siteName: 'Summit Site',
    address: '196 Cochise Blvd S',
    municipality: 'Douglas',
    state: 'AZ',
    status: 'Construction',
    urgency: 'critical',
    daysLeft: 0,
    shotClockLabel: 'Construction Deadline',
    constructionDate: '2026-06-05',
    applicationFee: 10500,
    approvedFee: 10500,
    towerOwner: 'Vertical Bridge',
    assignee: 'M. Garcia',
    projectType: 'New Wireless',
    latitude: 31.344,
    longitude: -109.543,
    pendingForms: ['NTP Brief — URGENT'],
    pendingApprovals: [],
    lastUpdated: '2026-06-04',
  },
  {
    id: 'TOWER-033',
    siteName: 'Elm Street',
    address: '700 E Elm St',
    municipality: 'Tucson',
    state: 'AZ',
    status: 'Closeout',
    urgency: 'ok',
    daysLeft: 18,
    shotClockLabel: 'Closeout Deadline',
    constructionDate: '2026-04-12',
    applicationFee: 17500,
    approvedFee: 17500,
    towerOwner: 'Crown Castle',
    assignee: 'R. Lopez',
    projectType: 'Colocation',
    latitude: 32.2226,
    longitude: -110.9747,
    pendingForms: ['Form 20 — As-Built Certification'],
    pendingApprovals: ['Final Inspection Sign-off'],
    lastUpdated: '2026-05-20',
  },
  {
    id: 'TOWER-078',
    siteName: 'Mesa Blanca',
    address: '3400 NM-528',
    municipality: 'Rio Rancho',
    state: 'NM',
    status: 'Submitted',
    urgency: 'ok',
    daysLeft: 21,
    shotClockLabel: 'Review Window',
    constructionDate: '2026-10-15',
    applicationFee: 10500,
    approvedFee: null,
    towerOwner: 'SBA Communications',
    assignee: 'T. Brown',
    projectType: 'New Wireless',
    latitude: 35.2328,
    longitude: -106.6956,
    pendingForms: [],
    pendingApprovals: ['Application Under Review'],
    lastUpdated: '2026-05-15',
  },
  {
    id: 'TOWER-101',
    siteName: 'Cedar Canyon',
    address: '8900 Cedar Ave',
    municipality: 'Farmington',
    state: 'NM',
    status: 'On Hold',
    urgency: 'hold',
    daysLeft: null,
    shotClockLabel: 'On Hold — Zoning Dispute',
    constructionDate: null,
    applicationFee: 17500,
    approvedFee: null,
    towerOwner: 'Vertical Bridge',
    assignee: 'M. Garcia',
    projectType: 'Eligible Facility',
    latitude: 36.7281,
    longitude: -108.2087,
    pendingForms: [],
    pendingApprovals: [],
    lastUpdated: '2026-04-01',
  },
];

export const MOCK_ACTIONS: ActionItem[] = [
  {
    id: 'a1',
    projectId: 'TOWER-006',
    siteName: 'Summit Site',
    type: 'form',
    label: 'NTP Brief — Submit Now',
    dueLabel: 'DUE TODAY',
    daysUntilDue: 0,
    urgency: 'critical',
  },
  {
    id: 'a2',
    projectId: 'TOWER-042',
    siteName: 'Maple Ridge',
    type: 'form',
    label: 'Form 7 — Site Certification',
    dueLabel: 'Due in 2 days',
    daysUntilDue: 2,
    urgency: 'critical',
  },
  {
    id: 'a3',
    projectId: 'TOWER-017',
    siteName: 'Westfield',
    type: 'approval',
    label: 'Zoning Approval — Review',
    dueLabel: 'Due in 5 days',
    daysUntilDue: 5,
    urgency: 'warning',
  },
  {
    id: 'a4',
    projectId: 'TOWER-055',
    siteName: 'Harbor Point',
    type: 'form',
    label: 'Form 12 — Zoning Package',
    dueLabel: 'Due in 8 days',
    daysUntilDue: 8,
    urgency: 'warning',
  },
  {
    id: 'a5',
    projectId: 'TOWER-033',
    siteName: 'Elm Street',
    type: 'approval',
    label: 'Final Inspection Sign-off',
    dueLabel: 'Due in 18 days',
    daysUntilDue: 18,
    urgency: 'ok',
  },
];

export const PORTFOLIO_STATS = {
  active: 3,
  permitting: 2,
  construction: 2,
  closeout: 1,
  onHold: 1,
  total: 8,
};
