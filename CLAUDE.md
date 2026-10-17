# CLAUDE.md — Daylight project rules (condensed copy)

> This is a condensed copy of the permanent project rules written from the project instructions in the chat (no CLAUDE.md file was found in the source repository).
> Replace it with the original full text if you have it; the full text wins on any difference.

1. **Follow the requirements.** Original Daylight project + approved plan + explicit owner instructions define the result. No redesign, rebranding, simplification or silent replacement of pages/features/fields/routes/integrations. Never invent business information, clients, testimonials, statistics or credentials. Ask only when a missing decision materially affects security, data integrity, cost or the result; otherwise take the safest assumption and document it.
2. **Inspect before editing.** Read this file and the docs, inspect the relevant code/schema/integrations, find the root cause, check git state and protect unrelated work.
3. **Protect the original.** Keep the React project recoverable; work on a separate branch/copy; never delete the repo, rewrite history, force-push, overwrite production or run destructive DB operations on real data without explicit authorisation; no secrets in git.
4. **Stack.** PHP + HTML/CSS + JavaScript + MySQL/MariaDB; no React at runtime. No new framework/runtime/database/major dependency without documented need. (Current decision: native PHP, because the first host is InfinityFree.)
5. **No fake functionality.** Every interactive element works; server-side validation; real auth and server-side permissions; success messages only after success; no hardcoded fake writes or placeholder handlers. Document blockers.
6. **Security is mandatory.** Prepared queries, hashed passwords, CSRF, validation, output escaping, access control, secure sessions/uploads/recovery, secrets in environment config, no stack traces to visitors, never disable controls to make something work, test authorised and unauthorised access.
7. **Preserve visual accuracy.** Original is the reference: tokens, layout, typography, spacing, colours, animations, responsive behaviour. Compare screenshots at equivalent sizes; document unavoidable differences.
8. **Verify every change.** Run syntax/build/tests, exercise routes and journeys, check logs, review for security, fix regressions, report what could not be verified. Never claim a test passed unless it ran; code inspection is not execution.
9. **Control scope.** Smallest maintainable change; no unnecessary refactoring or dependencies; do not weaken tests or suppress errors.
10. **Keep docs current.** CLAUDE.md, MIGRATION_PLAN.md, FEATURE_CHECKLIST.md, TEST_REPORT.md, README.md (and COMPUTER_SETUP_CHECKLIST.md while setup is deferred).
11. **Handle blockers honestly.** Explain the blocker, what is/is not done, continue independent work, offer a recovery path, record it in the checklist.
12. **Work autonomously but safely.** Follow the plan in order; request approval before irreversible, destructive, externally visible or costly actions; no deploys, live DNS changes, production data changes or real customer emails without authorisation.
13. **Maintain context across sessions.** Update checklist and test report; record phase, completed tasks, failed tests, blockers, next task and files, key decisions and reasons; read the notes before resuming.
14. **Definition of done.** Change exists in the right files, follows the architecture and design, no known regressions, relevant tests run and recorded, security considered, docs updated, limitations disclosed. Overall: working PHP app, verified DB integration, accounted-for original features, visual comparison, production setup docs, honest record of outstanding issues.

Status vocabulary used in this repo: **Implemented** (code written) · **Statically checked** · **Tested** (the test actually ran) · **Verified** (behaviour confirmed in the real environment) · **Blocked**.
