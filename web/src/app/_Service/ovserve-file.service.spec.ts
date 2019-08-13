import { TestBed } from '@angular/core/testing';

import { OvserveFileService } from './ovserve-file.service';

describe('OvserveFileService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: OvserveFileService = TestBed.get(OvserveFileService);
    expect(service).toBeTruthy();
  });
});
