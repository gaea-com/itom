import { TestBed } from '@angular/core/testing';

import { OvserveWSService } from './ovserve-ws.service';

describe('OvserveWSService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: OvserveWSService = TestBed.get(OvserveWSService);
    expect(service).toBeTruthy();
  });
});
