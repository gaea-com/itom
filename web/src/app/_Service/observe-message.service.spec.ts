import { TestBed } from '@angular/core/testing';

import { ObserveMessageService } from './observe-message.service';

describe('ObserveMessageService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ObserveMessageService = TestBed.get(ObserveMessageService);
    expect(service).toBeTruthy();
  });
});
