## Beads Workflow (Issue Tracking)

> **Context Recovery**: Run `bd prime` after compaction, clear, or new session

### Core Rules
- Track strategic work in beads (multi-session, dependencies, discovered work)
- Use `bd create` for issues, TodoWrite for simple single-session execution
- When in doubt, prefer bd—persistence beats lost context
- Git workflow: hooks auto-sync, run `bd sync` at session end
- Session management: check `bd ready` for available work

### Essential Commands

**Finding Work:**
- `bd ready` - Show issues ready to work (no blockers)
- `bd list --status=open` - All open issues
- `bd list --status=in_progress` - Your active work
- `bd show <id>` - Detailed issue view with dependencies

**Creating & Updating:**
- `bd create --title="..." --type=task|bug|feature --priority=2` - New issue
  - Priority: 0-4 or P0-P4 (0=critical, 2=medium, 4=backlog). NOT "high"/"medium"/"low"
- `bd update <id> --status=in_progress` - Claim work
- `bd close <id>` - Mark complete
- `bd close <id1> <id2> ...` - Close multiple issues at once

**Dependencies:**
- `bd dep add <issue> <depends-on>` - Add dependency
- `bd blocked` - Show all blocked issues

**Sync:**
- `bd sync` - Sync with git remote (run at session end)
- `bd stats` - Project statistics
- `bd doctor` - Check for issues

---

## 🚨 SESSION CLOSE PROTOCOL 🚨

**CRITICAL**: Before saying "done" or "complete", you MUST run this checklist:

```bash
[ ] 1. git status              # check what changed
[ ] 2. git add <files>         # stage code changes
[ ] 3. bd sync                 # commit beads changes
[ ] 4. git commit -m "..."     # commit code
[ ] 5. bd sync                 # commit any new beads changes
[ ] 6. git push                # push to remote
```

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing - that leaves work stranded locally
- NEVER say "ready to push when you are" - YOU must push
- If push fails, resolve and retry until it succeeds
