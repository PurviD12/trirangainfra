import { TestBed } from '@angular/core/testing';

import { MasterServicesService } from './master-services.service';

describe('MasterServicesService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: MasterServicesService = TestBed.get(MasterServicesService);
    expect(service).toBeTruthy();
  });
});
