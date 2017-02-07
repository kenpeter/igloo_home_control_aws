// Hak
// http://stackoverflow.com/questions/35721206/how-to-enable-production-mode-in-angular-2
import {enableProdMode} from '@angular/core';

import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';
import { AppModule } from './app.module';

// Hak
enableProdMode();

const platform = platformBrowserDynamic();
platform.bootstrapModule(AppModule);
