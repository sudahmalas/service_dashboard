import { execSync } from 'child_process';
import concurrently from 'concurrently';
import fs from 'fs';
import path from 'path';

// ── Read .env for Laravel and Reverb ports ────────────────────────────────────
const envPath = path.join(process.cwd(), '.env');
let appPort = '8000';
let reverbPort = '8090';

if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf8');
    const appPortMatch = envContent.match(/^APP_URL=.*:(\d+)/m);
    if (appPortMatch && appPortMatch[1]) appPort = appPortMatch[1].trim();

    const reverbMatch = envContent.match(/^REVERB_PORT=(\d+)/m);
    if (reverbMatch && reverbMatch[1]) reverbPort = reverbMatch[1].trim();
}

const isWindows = process.platform === 'win32';

// ── Kill a port on Windows / Linux ───────────────────────────────────────────
function killPort(port) {
    try {
        if (isWindows) {
            let pids = [];
            try {
                const psCmd = `powershell -NoProfile -Command "Get-NetTCPConnection -LocalPort ${port} -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess"`;
                const output = execSync(psCmd, { encoding: 'utf8', stdio: ['pipe', 'pipe', 'ignore'] }).trim();
                pids = output.split(/[\r\n]+/).map(s => s.trim()).filter(s => s && s !== '0' && /^\d+$/.test(s));
            } catch {
                const netstatOutput = execSync(`netstat -ano 2>nul`, { encoding: 'utf8', stdio: ['pipe', 'pipe', 'ignore'] });
                const lines = netstatOutput.split('\n');
                for (const line of lines) {
                    if (line.includes(`:${port} `) && line.includes('LISTENING')) {
                        const parts = line.trim().split(/\s+/);
                        const pid = parts[parts.length - 1];
                        if (pid && pid !== '0' && /^\d+$/.test(pid)) {
                            pids.push(pid);
                        }
                    }
                }
            }

            pids = [...new Set(pids)];

            for (const p of pids) {
                try {
                    execSync(`taskkill /PID ${p} /F /T 2>nul`, { stdio: 'ignore' });
                    console.log(`  [kill-port] ✓ Port ${port} — PID ${p} terminated`);
                } catch {}
            }

            if (pids.length === 0) {
                console.log(`  [kill-port] Port ${port} is free`);
            }
        } else {
            execSync(`lsof -ti tcp:${port} | xargs kill -9 2>/dev/null || true`);
            console.log(`  [kill-port] ✓ Port ${port} cleared`);
        }
    } catch {
        console.log(`  [kill-port] Port ${port} is free`);
    }
}

function killAllPorts(ports) {
    console.log(`\n[DevSetup] Clearing ports: ${ports.join(', ')} ...`);
    for (const port of ports) {
        killPort(port);
    }
    console.log('');
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ── Main Runner ──────────────────────────────────────────────────────────────
async function run() {
    const portsToKill = ['5175', appPort, reverbPort];
    killAllPorts(portsToKill);

    await sleep(500);

    console.log('========================================================');
    console.log('  🚀 Starting ID-Grow WebHost (Dev Mode)');
    console.log(`  • Laravel Web & API : http://127.0.0.1:${appPort}`);
    console.log(`  • Reverb WebSocket  : ws://127.0.0.1:${reverbPort}`);
    console.log('  • Vite Dev Server   : http://127.0.0.1:5175');
    console.log('========================================================\n');

    const commands = [
        {
            command: 'php artisan serve --port=' + appPort,
            name: 'LARAVEL',
            prefixColor: 'cyan',
        },
        {
            command: 'php artisan reverb:start --port=' + reverbPort,
            name: 'REVERB',
            prefixColor: 'magenta',
        },
        {
            command: 'npx vite',
            name: 'VITE',
            prefixColor: 'blue',
        },
    ];

    const { result } = concurrently(commands, {
        killOthers: ['failure', 'success'],
        restartTries: 0,
    });

    result.catch(() => {
        // Handled on close
    });
}

run();
