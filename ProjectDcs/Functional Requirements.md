# Functional Requirements - CRM Access Control (FR-002-CRM-Access-RBAC.md)

## Description
Implement RBAC functionality for the CRM module to control access to customer and opportunity data based on hierarchical roles and team assignments.

## Functionality

### 1. Role Assignment
- Assign roles (Administrator, TeamMember, RegularUser) to CRM users
- Map roles to access levels (FULL, TEAM, READ_ONLY)
- Automatically assign roles based on department/team membership

### 2. Access Control Enforcement
- Check RBAC permissions before allowing record access
- Validate user can view/modify requested CRM entity
- Return appropriate access decision (ALLOW/DENY)

### 3. Permission Matrix
| Role | Access Level | Description |
|------|--------------|-------------|
| Administrator | FULL | Complete access to all CRM entities |
| Team Member | TEAM | Access limited to own team/department |
| Regular User | READ_ONLY | Read-only access to CRM records |

### 4. Default Fallback
- If RBAC extension is not installed → Default to READ_ONLY
- CRM Administrators bypass default and get FULL access
- All other users follow their assigned role permissions

### 5. Session Caching
- Cache RBAC lookup results to avoid repeated queries
- Refresh cache periodically or on role changes
- Reduce database load for frequently accessed permissions

## Data Flow
1. User attempts to access CRM record
2. System retrieves user's role and permissions
3. RBAC check performed against permission matrix
4. Access granted or denied based on result
5. Response sent to caller

## Error Handling
- Log failed RBAC checks for debugging
- Graceful degradation to READ_ONLY when RBAC unavailable
- Clear error messages for unauthorized access attempts

## Testing
- Verify Administrator can access all CRM entities
- Verify Team Member can only access own team's data
- Verify Regular User has read-only access
- Confirm default READ_ONLY when RBAC not installed
- Test caching mechanism under load
