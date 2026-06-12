import * as esbuild from 'esbuild';

await esbuild.build({
    entryPoints: ['resources/js/autocomplete.js'],
    outfile: 'dist/components/autocomplete.js',
    bundle: true,
    minify: true,
    format: 'esm',
    platform: 'browser',
});

console.log('Built dist/components/autocomplete.js');
