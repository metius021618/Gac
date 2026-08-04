# Modo Viaje Implementation Plan

> **For agentic workers:** Execute task-by-task. Steps use checkbox syntax.

**Goal:** Public `/MViaje` consult + admin MODO VIAJE subjects CRUD + cron exact-subject ingest.

**Architecture:** Add `category` on `email_subjects`; filter admin UI by category; `CodeService::consultCodeModoViaje` loads subject lines and finds latest matching `codes` row; reuse Hogar UX.

**Tech Stack:** PHP MVC, MySQL, existing email_subjects CRUD JS, Python email_filter, PHPUnit.

---

### Task 1: Migration + repository category
### Task 2: Admin CRUD UI section MODO VIAJE
### Task 3: /MViaje consult (controller, service, view, JS)
### Task 4: Hogar button + cron already loads all subjects (verify)
### Task 5: Tests via controller payload (not raw INSERT) + filter unit test
### Task 6: Deploy migration + pull on SiteGround
