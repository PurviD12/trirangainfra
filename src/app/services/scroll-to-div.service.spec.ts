import { TestBed } from '@angular/core/testing';

import { ScrollToDivService } from './scroll-to-div.service';

describe('ScrollToDivService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ScrollToDivService = TestBed.get(ScrollToDivService);
    expect(service).toBeTruthy();
  });
});
