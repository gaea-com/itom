import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { WebShellComponent } from './web-shell.component';

describe('WebShellComponent', () => {
  let component: WebShellComponent;
  let fixture: ComponentFixture<WebShellComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ WebShellComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(WebShellComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
