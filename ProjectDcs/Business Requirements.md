# Business Requirements - CRM Access Control (BR-002-CRM-Access-RBAC.md)

## Overview
Implement Role-Based Access Control (RBAC) for the CRM module to enforce access control based on user roles, teams, and positions.

## Scope
- Define access levels for CRM modules (Admin, Team Members, Regular Users)
- Establish permission matrix linking roles to departments and teams
- Configure default access policies (READ ONLY, TEAM, FULL)

## Requirements

### 1. Role Hierarchy
- **Administrator** - Full access to all CRM records
- **Team Member** - Access limited to own team/department
- **Regular User** - Read-only access to CRM records

### 2. Access Levels
- **FULL** - Complete access to all CRM entities (customers, leads, opportunities)
- **TEAM** - Access restricted to own team/department
- **READ_ONLY** - Read-only access to CRM records

### 3. Default Behavior
- If RBAC is not installed → Default to READ_ONLY
- CRM users with Admin FA security → Full access
- All other users → READ_ONLY or TEAM based on assignment

### 4. Integration Points
- CRM module: Apply RBAC when accessing customer/lead records
- HRM module: Cross-module access validation
- Project Management: Apply RBAC for cross-module access

## Implementation Notes
- Leverage existing CRM entity hierarchy (Customer → Lead → Opportunity)
- Use RBAC grid helper for consistent permission mapping
- Cache RBAC queries to improve performance
- Document permission matrix in RbacGridHelpers.php

## Acceptance Criteria
- [ ] CRM Admins can access all records
- [ ] Team members can only access their assigned team/department
- [ ] Regular users have READ_ONLY access
- [ ] RBAC queries are cached for performance
- [ ] Default fallback to READ_ONLY when RBAC not configured
