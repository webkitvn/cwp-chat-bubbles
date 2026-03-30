#!/usr/bin/env node

import { build } from 'esbuild';
import * as sass from 'sass';
import autoprefixer from 'autoprefixer';
import postcss from 'postcss';
import { watch } from 'chokidar';
import { promises as fs } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Parse command line arguments
const args = process.argv.slice(2);
const isDev = args.includes('--dev');
const isProduction = args.includes('--production');
const isWatch = args.includes('--watch');

// Build configuration
const config = {
  mode: isDev ? 'development' : isProduction ? 'production' : 'development',
  sourceMap: isDev,
  minify: isProduction,
  watch: isWatch
};

console.log(`🚀 CWP Chat Bubbles Build System`);
console.log(`📦 Mode: ${config.mode}`);
console.log(`🔍 Source Maps: ${config.sourceMap ? 'enabled' : 'disabled'}`);
console.log(`📉 Minification: ${config.minify ? 'enabled' : 'disabled'}`);
console.log(`👀 File Watching: ${config.watch ? 'enabled' : 'disabled'}`);
console.log('');

// Ensure output directories exist
async function ensureDirectories() {
  try {
    await fs.mkdir(resolve(__dirname, 'assets'), { recursive: true });
    await fs.mkdir(resolve(__dirname, 'assets/css'), { recursive: true });
    await fs.mkdir(resolve(__dirname, 'assets/js'), { recursive: true });
    await fs.mkdir(resolve(__dirname, 'admin/css'), { recursive: true });
  } catch (error) {
    console.error('❌ Error creating directories:', error);
  }
}

// JavaScript build with esbuild
async function buildJavaScript() {
  const startTime = Date.now();
  
  try {
    const buildTargets = [
      {
        entryPoints: [resolve(__dirname, 'src/js/chat-bubble.js')],
        outfile: resolve(__dirname, 'assets/js/chat-bubbles.min.js'),
        globalName: 'CWPChatBubbles'
      },
      {
        entryPoints: [resolve(__dirname, 'src/js/admin.js')],
        outfile: resolve(__dirname, 'admin/js/admin.js')
      }
    ];

    const results = await Promise.all(buildTargets.map((target) => build({
      entryPoints: target.entryPoints,
      bundle: true,
      outfile: target.outfile,
      format: 'iife', // WordPress compatible format
      globalName: target.globalName,
      target: ['es2018'],
      minify: config.minify,
      sourcemap: config.sourceMap ? 'linked' : false,
      define: {
        'process.env.NODE_ENV': JSON.stringify(config.mode)
      },
      banner: {
        js: config.mode === 'production' 
          ? '/*! CWP Chat Bubbles - WordPress Plugin - https://github.com/cwp/cwp-chat-bubbles */'
          : '/* CWP Chat Bubbles - Development Build */'
      },
      platform: 'browser',
      charset: 'utf8',
      legalComments: config.minify ? 'none' : 'inline',
      external: [],
    })));

    const buildTime = Date.now() - startTime;

    const errors = results.flatMap((result) => result.errors);
    if (errors.length > 0) {
      console.error('❌ JavaScript build errors:');
      errors.forEach(error => console.error(error));
      return false;
    }

    const warnings = results.flatMap((result) => result.warnings);
    if (warnings.length > 0) {
      console.warn('⚠️  JavaScript build warnings:');
      warnings.forEach(warning => console.warn(warning));
    }

    console.log(`✅ JavaScript built in ${buildTime}ms`);
    return true;
  } catch (error) {
    console.error('❌ JavaScript build failed:', error);
    return false;
  }
}

// Frontend SCSS compilation
async function buildFrontendSCSS() {
  const startTime = Date.now();
  
  try {
    // Compile SCSS
    const result = sass.compile(resolve(__dirname, 'src/scss/_chat-bubbles.scss'), {
      style: config.minify ? 'compressed' : 'expanded',
      sourceMap: config.sourceMap,
      loadPaths: [resolve(__dirname, 'src/scss')],
      charset: true
    });

    let css = result.css;

    // Process with PostCSS and Autoprefixer
    const postcssResult = await postcss([
      autoprefixer({
        overrideBrowserslist: [
          'defaults',
          'not IE < 11',
          'not dead',
          '> 0.25%'
        ]
      })
    ]).process(css, {
      from: undefined,
      map: config.sourceMap ? { inline: false } : false
    });

    css = postcssResult.css;

    // Add banner comment
    const banner = config.mode === 'production'
      ? '/*! CWP Chat Bubbles - WordPress Plugin - https://github.com/cwp/cwp-chat-bubbles */\n'
      : '/* CWP Chat Bubbles - Development Build */\n';
    
    css = banner + css;

    // Write CSS file
    const cssPath = resolve(__dirname, 'assets/css/chat-bubbles.min.css');
    await fs.writeFile(cssPath, css, 'utf8');

    // Write source map if enabled
    if (config.sourceMap && postcssResult.map) {
      const mapPath = resolve(__dirname, 'assets/css/chat-bubbles.min.css.map');
      await fs.writeFile(mapPath, postcssResult.map.toString(), 'utf8');
    }

    const buildTime = Date.now() - startTime;
    console.log(`✅ Frontend SCSS compiled in ${buildTime}ms`);
    return true;
  } catch (error) {
    console.error('❌ Frontend SCSS compilation failed:', error);
    return false;
  }
}

// Admin SCSS compilation
async function buildAdminSCSS() {
  const startTime = Date.now();
  
  try {
    // Check if admin SCSS file exists
    const adminScssPath = resolve(__dirname, 'src/scss/admin.scss');
    
    try {
      await fs.access(adminScssPath);
    } catch {
      console.log('⏩ Admin SCSS file not found, skipping admin build');
      return true;
    }

    // Compile SCSS
    const result = sass.compile(adminScssPath, {
      style: config.minify ? 'compressed' : 'expanded',
      sourceMap: config.sourceMap,
      loadPaths: [resolve(__dirname, 'src/scss')],
      charset: true
    });

    let css = result.css;

    // Process with PostCSS and Autoprefixer
    const postcssResult = await postcss([
      autoprefixer({
        overrideBrowserslist: [
          'defaults',
          'not IE < 11',
          'not dead',
          '> 0.25%'
        ]
      })
    ]).process(css, {
      from: undefined,
      map: config.sourceMap ? { inline: false } : false
    });

    css = postcssResult.css;

    // Add banner comment
    const banner = config.mode === 'production'
      ? '/*! CWP Chat Bubbles Admin Styles - WordPress Plugin - https://github.com/cwp/cwp-chat-bubbles */\n'
      : '/* CWP Chat Bubbles Admin - Development Build */\n';
    
    css = banner + css;

    // Write CSS file
    const cssPath = resolve(__dirname, 'admin/css/admin.min.css');
    await fs.writeFile(cssPath, css, 'utf8');

    // Write source map if enabled
    if (config.sourceMap && postcssResult.map) {
      const mapPath = resolve(__dirname, 'admin/css/admin.min.css.map');
      await fs.writeFile(mapPath, postcssResult.map.toString(), 'utf8');
    }

    const buildTime = Date.now() - startTime;
    console.log(`✅ Admin SCSS compiled in ${buildTime}ms`);
    return true;
  } catch (error) {
    console.error('❌ Admin SCSS compilation failed:', error);
    return false;
  }
}

// Build all assets
async function buildAll() {
  console.log('📦 Building assets...');
  
  await ensureDirectories();
  
  const [jsSuccess, frontendScssSuccess, adminScssSuccess] = await Promise.all([
    buildJavaScript(),
    buildFrontendSCSS(),
    buildAdminSCSS()
  ]);

  if (jsSuccess && frontendScssSuccess && adminScssSuccess) {
    console.log('✅ All assets built successfully!');
    return true;
  } else {
    console.error('❌ Build failed!');
    return false;
  }
}

// File watcher for development
function startWatcher() {
  console.log('👀 Starting file watcher...');
  
  const jsWatcher = watch('src/js/**/*.js', { 
    ignored: /node_modules/,
    persistent: true 
  });
  
  const scssWatcher = watch('src/scss/**/*.scss', { 
    ignored: /node_modules/,
    persistent: true 
  });

  jsWatcher.on('change', async (path) => {
    console.log(`📝 JavaScript file changed: ${path}`);
    await buildJavaScript();
  });

  scssWatcher.on('change', async (path) => {
    console.log(`🎨 SCSS file changed: ${path}`);
    
    // Build both frontend and admin SCSS if any SCSS file changes
    await Promise.all([
      buildFrontendSCSS(),
      buildAdminSCSS()
    ]);
  });

  console.log('👁️  Watching for file changes... (Press Ctrl+C to stop)');

  // Handle graceful shutdown
  process.on('SIGINT', () => {
    console.log('\n🛑 Stopping file watcher...');
    jsWatcher.close();
    scssWatcher.close();
    process.exit(0);
  });
}

// Main function
async function main() {
  // Show help if no valid arguments provided
  if (!isDev && !isProduction) {
    console.log(`
Usage: node build.js [options]

Options:
  --dev         Development build (source maps, no minification)
  --production  Production build (minified, no source maps)
  --watch       Watch files for changes (requires --dev)

Examples:
  node build.js --dev            # Development build
  node build.js --dev --watch    # Development with file watching
  node build.js --production     # Production build

Note: Use pnpm scripts for convenience:
  pnpm run dev        # Same as --dev --watch
  pnpm run build      # Same as --production
  pnpm run build:dev  # Same as --dev
`);
    return;
  }

  // Validate watch mode
  if (isWatch && !isDev) {
    console.error('❌ Watch mode requires --dev flag');
    process.exit(1);
  }

  // Build assets
  const success = await buildAll();
  
  if (!success) {
    process.exit(1);
  }

  // Start file watcher if requested
  if (isWatch) {
    startWatcher();
  }
}

// Run the build
main().catch(error => {
  console.error('❌ Build process failed:', error);
  process.exit(1);
});
