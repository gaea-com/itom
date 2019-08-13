import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EnvComponent } from './env.component';

describe('EnvComponent', () => {
  let component: EnvComponent;
  let fixture: ComponentFixture<EnvComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EnvComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EnvComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
