import { readFileSync, mkdtempSync, writeFileSync, existsSync, chmodSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
import assert from 'node:assert/strict';
const root = mkdtempSync(join(tmpdir(), 'veloria-cron-gate-'));
const gate = join(root, 'approved');
const marker = join(root, 'ran');
const source = readFileSync(new URL('../deploy/storefront-cron.sh', import.meta.url), 'utf8')
  .replace('/home/veloria/veloria-tools/veloria-cutover-approved.flag', gate)
  .replace('/usr/bin/php', join(root, 'php'));
writeFileSync(join(root, 'cron.sh'), source);
writeFileSync(join(root, 'wp'), '#!/bin/sh\nprintf "%s" "$TEST_READINESS"\n');
writeFileSync(join(root, 'php'), `#!/bin/sh\ntouch '${marker}'\n`);
for (const command of ['wp', 'php']) chmodSync(join(root, command), 0o700);
const env = { ...process.env, PATH: `${root}:${process.env.PATH}`, TEST_READINESS: 'ready' };
const run = () => spawnSync('bash', [join(root, 'cron.sh')], { env, encoding:'utf8' });
assert.equal(run().status, 0); assert.equal(existsSync(marker), false, 'Missing approval never starts cron');
writeFileSync(gate, 'approved fixture'); env.TEST_READINESS = 'blocked';
assert.equal(run().status, 78); assert.equal(existsSync(marker), false, 'Wrong live state never starts cron');
env.TEST_READINESS = 'ready';
assert.equal(run().status, 0); assert.equal(existsSync(marker), true, 'Approved correct state starts native wp-cron');
console.log('3 cron-gate behaviour checks passed');
