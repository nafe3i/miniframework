# ADR-002 — MVC with an application service layer

**Status:** accepted

## Problem
Where do HTTP handling, business rules, persistence and HTML generation live?

## Options
1. "Fat controllers" doing validation and SQL.
2. MVC with a service layer and a repository: Controller → Service → Repository, Views for output.

## Decision
Option 2: `TaskController` (HTTP), `TaskService` (rules), `TaskRepositoryInterface`
/ `PdoTaskRepository` (persistence), PHP templates (presentation), `Task` entity (data).

## Justification
Each layer has one reason to change (SRP). The service is testable without
HTTP or a database. Controllers stay thin and readable.

## Consequences
* More files than a fat controller for a tiny CRUD — accepted for clarity.
* Controllers never contain SQL; views never call repositories.
* A JSON API (`TaskApiController`) reuses the same service with no duplication.
