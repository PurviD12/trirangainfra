import { Injectable } from '@angular/core';

@Injectable({
    providedIn: 'root'
})
export class ScrollToDivService {

    constructor() { }

    click_to_scroll(type: any) {
        const yOffset = -100; // adjust this value to set the vertical offset
        const element = document.getElementById(type);
        const y = element!.getBoundingClientRect().top + window.pageYOffset + yOffset;

        const options: ScrollToOptions = {
            behavior: 'smooth',
            // block: 'start',
            // inline: 'nearest'
        };

        window.scroll({ top: y, ...options });
    }
}