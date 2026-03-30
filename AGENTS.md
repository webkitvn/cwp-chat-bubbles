## Beads_Rust Workflow (Issue Tracking)

**Note:** `br` is non-invasive and never executes git commands. After `br sync --flush-only`, you must manually run `git add .beads/` and `git commit`.

> **Context Recovery**: Run `br ready` after compaction, clear, or new session

### Core Rules
- Track strategic work in beads (multi-session, dependencies, discovered work)
- Use `br create` for issues, TodoWrite for simple single-session execution
- When in doubt, prefer br—persistence beats lost context
- Git workflow: run `br sync --flush-only` at session end, then commit `.beads/` manually
- Session management: check `br ready` for available work

### Essential Commands

**Finding Work:**
- `br ready` - Show issues ready to work (no blockers)
- `br list --status=open` - All open issues
- `br list --status=in_progress` - Your active work
- `br show <id>` - Detailed issue view with dependencies

**Creating & Updating:**
- `br create --title="..." --type=task|bug|feature --priority=2` - New issue
  - Priority: 0-4 or P0-P4 (0=critical, 2=medium, 4=backlog). NOT "high"/"medium"/"low"
- `br update <id> --status=in_progress` - Claim work
- `br close <id>` - Mark complete
- `br close <id1> <id2> ...` - Close multiple issues at once

**Dependencies:**
- `br dep add <issue> <depends-on>` - Add dependency
- `br blocked` - Show all blocked issues

**Sync:**
- `br sync --flush-only` - Flush tracker changes to `.beads/`
- `git add .beads/` - Stage tracker changes after sync
- `git commit -m "sync beads"` - Commit tracker changes after sync
- `br stats` - Project statistics
- `br doctor` - Check for issues

---

## 🚨 SESSION CLOSE PROTOCOL 🚨

**CRITICAL**: Before saying "done" or "complete", you MUST run this checklist:

```bash
[ ] 1. git status              # check what changed
[ ] 2. git add <files>         # stage code changes
[ ] 3. git commit -m "..."     # commit code
[ ] 4. br sync --flush-only    # flush beads changes to .beads/
[ ] 5. git add .beads/         # stage beads changes
[ ] 6. git commit -m "sync beads"  # commit beads changes
[ ] 7. git push                # push to remote
```

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing - that leaves work stranded locally
- NEVER say "ready to push when you are" - YOU must push
- If push fails, resolve and retry until it succeeds
