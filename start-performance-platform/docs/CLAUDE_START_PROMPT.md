# Claude Code Start Prompt

We are building the Start Performance Platform.

This is a modular WordPress-based business automation platform.

The platform should use WordPress as the admin/user shell for now, but should be architected with custom database tables and REST APIs so it can eventually become a standalone SaaS.

## Current product pieces already built

- AI chatbot / conversations
- Admin dashboard
- Quote / estimate / proposal form
- Service ticket system
- Knowledge base
- Training center
- HubSpot integrations

## Target Architecture

### 1. Core Platform

- Users
- Permissions
- Contacts
- Companies
- Files
- Notifications
- Settings
- Integrations

### 2. AI Layer

- Website chat
- Internal chat
- Conversation history
- Prompt management
- Assistant settings
- Knowledge connections

### 3. Knowledge Layer

- Knowledge base
- Training center
- Documents
- FAQs
- SOPs

### 4. Business Cores

#### Sales Core

- Leads
- Pipeline
- Quotes
- Estimates
- Proposals

#### Service Core

- Tickets
- Service requests
- Customer portal
- Support history

#### Marketing Core

- Forms
- Campaigns
- Landing pages
- Content library

#### Operations Core

- Onboarding
- Scheduling
- Workflows
- Task tracking

#### Intelligence Core

- Dashboards
- Reporting
- KPIs
- AI insights

## Important Instruction

Do not start coding yet.

First inspect the existing files and produce:

1. File inventory
2. Current features found
3. Reusable code
4. Duplicate/conflicting code
5. Recommended plugin architecture
6. Recommended database tables
7. Recommended REST API endpoints
8. Build phases
9. Risks before coding

## Goal

Turn the existing separate tools into one unified modular WordPress platform.
