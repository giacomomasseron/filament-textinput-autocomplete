import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import source from '../../resources/js/autocomplete.js';
import distribution from '../../dist/components/autocomplete.js';

const spain = { value: 'es', label: 'Spain', html: 'Spain', keys: ['spain'] };
const france = { value: 'fr', label: 'France', html: 'France', keys: ['france'] };

function createComponent(factory, config = {}) {
    const component = factory({ options: [spain, france], ...config });
    component.$watch = (property, callback) => {
        component.stateChanged = callback;
    };
    component.init();
    return component;
}

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });
    return { promise, resolve, reject };
}

for (const [name, factory] of [['source', source], ['distribution', distribution]]) {
    describe(name, () => {
        it('shows the saved static option label without changing its value', () => {
            const component = createComponent(factory, { state: 'es' });

            assert.equal(component.query, 'Spain');
            assert.equal(component.state, 'es');
        });

        it('preserves free text and zero when initializing', () => {
            assert.equal(createComponent(factory, { state: 'Custom' }).query, 'Custom');
            assert.equal(createComponent(factory, { state: 0 }).query, '0');
        });

        it('saves typed text and clearing after selecting a suggestion', () => {
            const component = createComponent(factory);
            component.select(spain);

            component.onInput('Custom');
            assert.equal(component.state, 'Custom');
            assert.equal(component.query, 'Custom');
            component.onInput('');
            assert.equal(component.state, '');
        });

        it('keeps the selected value when the input is focused again', () => {
            const component = createComponent(factory);
            component.select(spain);

            component.search();

            assert.equal(component.query, 'Spain');
            assert.equal(component.state, 'es');
            assert.deepEqual(component.results, [spain]);
        });

        it('reflects external changes and form resets in the visible input', () => {
            const component = createComponent(factory, { state: 'es' });

            component.state = 'fr';
            component.stateChanged('fr');
            assert.equal(component.query, 'France');
            component.state = null;
            component.stateChanged(null);
            assert.equal(component.query, '');
        });

        it('keeps a server result label when its selected value is synchronized', () => {
            const component = createComponent(factory, { serverSearch: true, options: [] });
            component.select(spain);

            component.stateChanged('es');

            assert.equal(component.query, 'Spain');
            assert.equal(component.state, 'es');
        });

        it('ignores an old response while the newer search is still debouncing', async (t) => {
            t.mock.timers.enable({ apis: ['setTimeout'] });
            const response = deferred();
            const component = createComponent(factory, { serverSearch: true });
            component.$wire = { callSchemaComponentMethod: () => response.promise };
            component.query = 'Sp';
            const pending = component.fetchResults();

            component.query = 'Fr';
            component.search();
            response.resolve({ results: [spain] });
            await pending;

            assert.deepEqual(component.results, []);
            assert.equal(component.isLoading, true);
            component.close();
        });

        for (const fails of [false, true]) {
            it(`ignores stale ${fails ? 'errors' : 'results'} after clearing the query`, async () => {
                const response = deferred();
                const component = createComponent(factory, { serverSearch: true });
                component.$wire = { callSchemaComponentMethod: () => response.promise };
                component.query = 'Sp';
                const pending = component.fetchResults();

                component.query = '';
                component.search();
                if (fails) {
                    response.reject(new Error('Old error'));
                } else {
                    response.resolve({ results: [spain] });
                }
                await pending;

                assert.deepEqual(component.results, []);
                assert.equal(component.error, null);
                assert.equal(component.isLoading, false);
                assert.equal(component.isOpen, false);
            });
        }

        for (const action of ['close', 'destroy']) {
            it(`cancels a queued search on ${action}`, (t) => {
                t.mock.timers.enable({ apis: ['setTimeout'] });
                const component = createComponent(factory, { serverSearch: true });
                let calls = 0;
                component.$wire = { callSchemaComponentMethod: async () => {
                    calls++;
                    return { results: [] };
                } };
                component.query = 'Sp';
                component.search();

                component[action]();
                t.mock.timers.tick(300);

                assert.equal(calls, 0);
                assert.equal(component.isLoading, false);
            });
        }

        it('clears old suggestions before starting another server search', (t) => {
            t.mock.timers.enable({ apis: ['setTimeout'] });
            const component = createComponent(factory, { serverSearch: true });
            component.results = [spain];
            component.activeIndex = 0;
            component.query = 'Fr';

            component.search();
            component.selectActive();

            assert.deepEqual(component.results, []);
            assert.equal(component.activeIndex, -1);
            assert.equal(component.state, undefined);
            component.close();
        });

        it('uses the latest response when requests finish out of order', async () => {
            const first = deferred();
            const second = deferred();
            const component = createComponent(factory, { serverSearch: true });
            component.$wire = { callSchemaComponentMethod: () => first.promise };
            component.query = 'Sp';
            const older = component.fetchResults();
            component.$wire.callSchemaComponentMethod = () => second.promise;
            component.query = 'Fr';
            const newer = component.fetchResults();

            second.resolve({ results: [france] });
            await newer;
            first.resolve({ results: [spain] });
            await older;

            assert.deepEqual(component.results, [france]);
            assert.equal(component.isLoading, false);
        });

        it('ignores a response that arrives after selecting a suggestion', async () => {
            const response = deferred();
            const component = createComponent(factory, { serverSearch: true });
            component.$wire = { callSchemaComponentMethod: () => response.promise };
            component.query = 'Sp';
            const pending = component.fetchResults();

            component.select(france);
            response.resolve({ results: [spain] });
            await pending;

            assert.deepEqual(component.results, []);
            assert.equal(component.query, 'France');
            assert.equal(component.state, 'fr');
            assert.equal(component.isOpen, false);
        });
    });
}
